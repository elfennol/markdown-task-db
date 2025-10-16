<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Service;

readonly class SchemaBuilder
{
    public function __construct(private DbConnection $connection)
    {
    }

    public function build(): int|false
    {
        return $this->connection->get()->exec($this->getSql());
    }

    private function getSql(): string
    {
        return <<<'SQL'
drop table if exists project;
create table project (
    id char(36) primary key not null,
    name varchar(120) not null,
    metadata text
);
drop table if exists task;
create table task (
    id char(36) primary key not null,
    project_id integer not null,
    name varchar(120) not null,
    metadata text,
    foreign key (project_id) references project (id) on delete cascade
);
drop table if exists tracking;
create table tracking (
    id char(36) primary key not null,
    task_id integer not null,
    start integer not null,
    end integer not null,
    metadata text,
    foreign key (task_id) references task (id) on delete cascade
);
SQL;
    }
}
