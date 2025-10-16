<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Service;

readonly class State
{
    public function __construct(public string $value, public array $context)
    {
    }
}
