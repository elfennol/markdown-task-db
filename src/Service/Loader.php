<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Service;

readonly class Loader
{
    public function __construct(private DbConnection $connection)
    {
    }

    public function insertProject(array $project): bool
    {
        $sql = <<<'SQL'
insert into project (id, name, metadata)
values (:id, :name, :metadata)
SQL;
        $params = [
            'id' => $project['id'],
            'name' => $project['name'],
            'metadata' => $project['metadata']
        ];

        return $this->connection->get()->prepare($sql)->execute($params);
    }

    public function insertTask(array $task, string $projectId): bool
    {
        $sql = <<<'SQL'
insert into task (id, project_id, name, metadata)
values (:id, :projectId, :name, :metadata)
SQL;
        $params = [
            'id' => $task['id'],
            'projectId' => $projectId,
            'name' => $task['name'],
            'metadata' => $task['metadata']
        ];

        return $this->connection->get()->prepare($sql)->execute($params);
    }

    public function insertTracking(array $tracking, string $taskId): bool
    {
        $sql = <<<'SQL'
insert into tracking (id, task_id, start, end, metadata)
values (:id, :taskId, :start, :end, :metadata)
SQL;
        $params = [
            'id' => $tracking['id'],
            'taskId' => $taskId,
            'start' => $tracking['start'],
            'end' => $tracking['end'],
            'metadata' => $tracking['metadata'],
        ];

        return $this->connection->get()->prepare($sql)->execute($params);
    }
}
