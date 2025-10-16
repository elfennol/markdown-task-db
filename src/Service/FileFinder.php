<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Service;

use Symfony\Component\Finder\Finder;

readonly class FileFinder
{
    /**
     * @return iterable<string>
     */
    public function find(string $directory): iterable
    {
        $finder = new Finder();
        $finder->files()->in($directory)->name('*.md');

        foreach ($finder as $file) {
            yield $file->getRealPath();
        }
    }
}
