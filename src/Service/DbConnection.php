<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Service;

use PDO;

class DbConnection
{
    private ?PDO $connection = null;

    public function __construct(private readonly string $dbUrl)
    {
    }

    public function get(): PDO
    {
        if (null === $this->connection) {
            $this->connection = PDO::connect($this->dbUrl);
        }

        return $this->connection;
    }
}
