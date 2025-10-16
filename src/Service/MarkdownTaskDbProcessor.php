<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Service;

use Psr\Log\LoggerInterface;

readonly class MarkdownTaskDbProcessor
{
    public function __construct(
        private SchemaBuilder $schemaBuilder,
        private FileFinder $fileFinder,
        private FileProcessor $fileProcessor,
        private Loader $loader,
        private LoggerInterface $logger,
    ) {
    }

    public function process(string $directory): void
    {
        $queryResult = $this->schemaBuilder->build();
        if (false === $queryResult) {
            $this->logger->error('Create tables failed');

            return;
        }

        foreach ($this->fileFinder->find($directory) as $file) {
            $fileProcessed = $this->fileProcessor->process($file);
            if (empty($fileProcessed['project']['id'])) {
                $this->logger->error('No project', ['fileProcessed' => $fileProcessed]);
                break;
            }
            $queryResult = $this->loader->insertProject($fileProcessed['project']);
            if (false === $queryResult) {
                $this->logger->error('SQL insert for project failed', ['fileProcessed' => $fileProcessed]);
                break;
            }
            foreach ($fileProcessed['tasks'] ?? [] as $task) {
                $taskId = $task['id'] ?? '';
                if (empty($taskId)) {
                    $this->logger->error('TaskId is empty', [
                        'fileProcessed' => $fileProcessed,
                        'task' => $task,
                    ]);
                    break;
                }
                $queryResult = $this->loader->insertTask($task, $fileProcessed['project']['id']);
                if (false === $queryResult) {
                    $this->logger->error('SQL insert for task failed', [
                        'fileProcessed' => $fileProcessed,
                        'task' => $task,
                    ]);
                    break;
                }
                foreach ($fileProcessed['tasks'][$taskId]['tracking'] ?? [] as $tracking) {
                    $queryResult = $this->loader->insertTracking($tracking, $taskId);
                    if (false === $queryResult) {
                        $this->logger->error('SQL insert for tracking failed', [
                            'fileProcessed' => $fileProcessed,
                            'tracking' => $tracking,
                        ]);
                        break;
                    }
                }
            }
        }
    }
}
