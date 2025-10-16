<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Service;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

readonly class FileProcessor
{
    private const string STATE_PROJECT = 'project';
    private const string STATE_TASKS = 'tasks';
    private const string STATE_TASK = 'task';
    private const string STATE_TRACKING = 'tracking';
    private const string STATE_END = 'end';

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function process(string $file): array
    {
        $lineParsed = [];
        $state = new State(self::STATE_PROJECT, []);
        $handle = fopen($file, "r");
        if (false === $handle) {
            $this->logger->error('Unable to open file', ['file' => $file]);

            return $lineParsed;
        }
        while (!feof($handle) && false !== ($line = fgets($handle)) && self::STATE_END !== $state->value) {
            $state = $this->project($state, $lineParsed, $line, $file, $handle);
            $state = $this->tasks($state, $lineParsed, $file, $handle);
        }
        fclose($handle);

        return $lineParsed;
    }

    /**
     * @param resource $handle
     */
    private function tracking(State $state, array &$lineParsed, string $file, mixed $handle): State
    {
        $nextState = new State(self::STATE_TASK, []);
        if (self::STATE_TRACKING === $state->value) {
            $taskId = $state->context['taskId'];
            while (!feof($handle) && false !== ($line = fgets($handle))) {
                if ($this->isFirstHeading($line)) {
                    $nextState = new State(self::STATE_END, []);
                    break;
                }
                if ($this->isSecondHeading($line)) {
                    $nextState = new State(self::STATE_TASK, ['line' => $line]);
                    break;
                }
                if ($this->isTrackingHeading($line)) {
                    while (!feof($handle) && false !== ($line = fgets($handle))) {
                        if ($this->isHeading($line)) {
                            $this->logger->error(
                                'Metadata required for tracking',
                                ['file' => $file, 'task' => $lineParsed['tasks'][$taskId]['name']]
                            );
                        }
                        if ($this->isMetadata($line)
                            && null !== ($extractedMetadata = $this->extractMetadata($file, $handle))) {
                            $trackingList = json_decode($extractedMetadata, true);
                            if (!is_array($trackingList)) {
                                $this->logger->error(
                                    'Unable to decode tracking metadata',
                                    ['file' => $file, 'metadata' => $extractedMetadata]
                                );
                                break;
                            }
                            foreach ($trackingList as $tracking) {
                                $trackingId = Uuid::v7()->toString();
                                if (false === DateTimeImmutable::createFromFormat('Y-m-d H:i', $tracking[0])
                                    || false === DateTimeImmutable::createFromFormat('Y-m-d H:i', $tracking[1])
                                ) {
                                    $this->logger->error('Invalid date', ['file' => $file, 'tracking' => $tracking]);
                                    continue;
                                }
                                $lineParsed['tasks'][$taskId]['tracking'][$trackingId]['id'] = $trackingId;
                                $lineParsed['tasks'][$taskId]['tracking'][$trackingId]['start'] = $tracking[0];
                                $lineParsed['tasks'][$taskId]['tracking'][$trackingId]['end'] = $tracking[1];
                                $lineParsed['tasks'][$taskId]['tracking'][$trackingId]['metadata'] = json_encode(
                                    $tracking[2]
                                );
                            }
                            break;
                        }
                    }

                    break;
                }
            }
        }

        return $nextState;
    }

    /**
     * @param resource $handle
     */
    private function task(State $state, array &$lineParsed, string $line, string $file, mixed $handle): State
    {
        $nextState = $state;
        if (self::STATE_TASK === $state->value && $this->isTaskHeading($line)) {
            $taskId = Uuid::v7()->toString();
            $lineParsed['tasks'][$taskId]['id'] = $taskId;
            $lineParsed['tasks'][$taskId]['name'] = $this->extractHeading($line);
            while (!feof($handle) && false !== ($line = fgets($handle))) {
                if ($this->isHeading($line)) {
                    $this->logger->error(
                        'Metadata required for task',
                        ['file' => $file, 'task' => $lineParsed['tasks'][$taskId]['name']]
                    );
                }
                if ($this->isMetadata($line)) {
                    $lineParsed['tasks'][$taskId]['metadata'] = $this->extractMetadata($file, $handle);
                    break;
                }
            }
            $nextState = $this->tracking(
                new State(self::STATE_TRACKING, ['taskId' => $taskId]),
                $lineParsed,
                $file,
                $handle
            );
        }

        return $nextState;
    }

    /**
     * @param resource $handle
     */
    private function tasks(State $state, array &$lineParsed, string $file, mixed $handle): State
    {
        $nextState = $state;
        if (self::STATE_TASKS === $state->value) {
            while (!feof($handle) && false !== ($line = fgets($handle))) {
                if ($this->isTasksHeading($line)) {
                    $nextState = new State(self::STATE_TASK, []);
                    while (!feof($handle)
                        && false !== ($line = !empty($nextState->context['line'])
                            ? $nextState->context['line']
                            : fgets($handle))
                        && self::STATE_END !== $nextState->value
                    ) {
                        if ($this->isFirstHeading($line)) {
                            $nextState = new State(self::STATE_END, []);
                            break;
                        }
                        $nextState = $this->task(new State(self::STATE_TASK, []), $lineParsed, $line, $file, $handle);
                    }

                    break;
                }
            }
        }

        return $nextState;
    }

    /**
     * @param resource $handle
     */
    private function project(State $state, array &$lineParsed, string $line, string $file, mixed $handle): State
    {
        $nextState = $state;
        if (self::STATE_PROJECT === $state->value && $this->isProjectHeading($line)) {
            $lineParsed['project']['id'] = Uuid::v7()->toString();
            $lineParsed['project']['name'] = $this->extractHeading($line);
            $nextState = new State(self::STATE_TASKS, []);
            while (!feof($handle) && false !== ($line = fgets($handle))) {
                if ($this->isHeading($line)) {
                    $this->logger->error('Metadata required for project', ['file' => $file]);
                }
                if ($this->isMetadata($line)) {
                    $lineParsed['project']['metadata'] = $this->extractMetadata($file, $handle);
                    break;
                }
            }
        }

        return $nextState;
    }

    /**
     * @param resource $handle
     */
    private function extractMetadata(string $file, mixed $handle): ?string
    {
        $metadata = null;
        $line = fgets($handle);
        while (false !== $line && false === str_starts_with($line, '```') && !feof($handle)) {
            $metadata .= $line;
            $line = fgets($handle);
        }

        if (!json_validate($metadata ?? '')) {
            $this->logger->error('Invalid metadata', ['file' => $file, 'metadata' => $metadata]);

            return null;
        }

        return $metadata;
    }

    private function isTrackingHeading(string $line): bool
    {
        return str_starts_with($line, '### Tracking');
    }

    private function isTaskHeading(string $line): bool
    {
        return str_starts_with($line, '## ');
    }

    private function isTasksHeading(string $line): bool
    {
        return str_starts_with($line, '# Tasks');
    }

    private function isProjectHeading(string $line): bool
    {
        return $this->isFirstHeading($line);
    }

    private function extractHeading(string $line): string
    {
        preg_match('/^#+ (.*)/', $line, $matches);

        return $matches[1] ?? '';
    }

    private function isMetadata(string $line): bool
    {
        return str_starts_with($line, '```json');
    }

    private function isSecondHeading(string $line): bool
    {
        return str_starts_with($line, '## ');
    }

    private function isFirstHeading(string $line): bool
    {
        return str_starts_with($line, '# ');
    }

    private function isHeading(string $line): bool
    {
        return str_starts_with($line, '#');
    }
}
