<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Command;

use Elfennol\MarkdownTaskDb\Service\MarkdownTaskDbProcessor;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'markdown-task-db')]
readonly class MarkdownTaskDbCommand
{
    public function __construct(private MarkdownTaskDbProcessor $processor)
    {
    }

    public function __invoke(#[Argument('Path to the markdown task files')] string $directory): int
    {
        $this->processor->process($directory);

        return Command::SUCCESS;
    }
}
