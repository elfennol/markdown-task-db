<?php

declare(strict_types=1);

namespace Elfennol\MarkdownTaskDb\Tests;

use Elfennol\MarkdownTaskDb\Service\DbConnection;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MarkdownTaskDbTest extends KernelTestCase
{
    #[DataProvider('provider')]
    public function testMarkdownTaskDb(string $sql, array $expected): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('markdown-task-db');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'directory' => sprintf('%s/fixtures/', __DIR__)
        ]);

        $commandTester->assertCommandIsSuccessful();

        $container = static::getContainer();
        /** @var DbConnection $connection */
        $connection = $container->get(DbConnection::class);

        $queryResult = $connection->get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        self::assertEqualsCanonicalizing($expected, $queryResult);
    }

    public static function provider(): array
    {
        return [
            [self::sqlSimple(), self::expectedSimple()],
            [self::sqlTimeByTaskOnDateInterval(), self::expectedTimeByTaskOnDateInterval()],
            [self::sqlGroupByMetadata(), self::expectedGroupByMetadata()],
        ];
    }

    private static function sqlSimple(): string
    {
        return <<<'SQL'
select p.name as project_name,
       t.name as task_name,
       tr.start,
       tr.end,
       json_extract (p.metadata, '$.client') as project_client,
       json_extract (t.metadata, '$.status') as task_status,
       json_extract (tr.metadata, '$.category') as tracking_category
from project p
inner join task t on t.project_id = p.id
left join tracking tr on tr.task_id = t.id;
SQL;
    }

    private static function expectedSimple(): array
    {
        return [
            [
                'project_name' => 'Project 1',
                'task_name' => 'My task 1.1',
                'start' => '2025-01-01 08:00',
                'end' => '2025-01-01 09:00',
                'project_client' => 'ACME',
                'task_status' => 'doing',
                'tracking_category' => 'dev'
            ],
            [
                'project_name' => 'Project 1',
                'task_name' => 'My task 1.1',
                'start' => '2025-01-01 09:00',
                'end' => '2025-01-01 10:00',
                'project_client' => 'ACME',
                'task_status' => 'doing',
                'tracking_category' => 'meeting'
            ],
            [
                'project_name' => 'Project 1',
                'task_name' => 'My task 1.1',
                'start' => '2025-01-03 10:00',
                'end' => '2025-01-03 12:00',
                'project_client' => 'ACME',
                'task_status' => 'doing',
                'tracking_category' => 'dev'
            ],
            [
                'project_name' => 'Project 1',
                'task_name' => 'My Task 1.2',
                'start' => null,
                'end' => null,
                'project_client' => 'ACME',
                'task_status' => 'todo',
                'tracking_category' => null
            ],
            [
                'project_name' => 'Project 1',
                'task_name' => 'My task 1.3',
                'start' => '2025-01-02 08:00',
                'end' => '2025-01-02 09:00',
                'project_client' => 'ACME',
                'task_status' => 'doing',
                'tracking_category' => 'dev'
            ],
            [
                'project_name' => 'Project 2',
                'task_name' => 'My task 2.1',
                'start' => '2025-01-04 09:00',
                'end' => '2025-01-04 12:00',
                'project_client' => 'EMCA',
                'task_status' => 'done',
                'tracking_category' => 'dev'
            ],
            [
                'project_name' => 'Project 4',
                'task_name' => 'My task 4.1',
                'start' => null,
                'end' => null,
                'project_client' => null,
                'task_status' => 'done',
                'tracking_category' => null
            ]
        ];
    }

    private static function sqlTimeByTaskOnDateInterval(): string
    {
        return <<<'SQL'
select p.name,
       t.name,
       round(sum(julianday(tr.end) - julianday(tr.start)) * 24, 0) as time
from project p
inner join task t on t.project_id = p.id
left join tracking tr on tr.task_id = t.id
where p.name = 'Project 1'
  and date(tr.start) >= date('2025-01-01')
  and date(tr.end) <= date('2025-01-31')
group by t.id;
SQL;
    }

    private static function expectedTimeByTaskOnDateInterval(): array
    {
        return [
            [
                'name' => 'My task 1.1',
                'time' => 4
            ],
            [
                'name' => 'My task 1.3',
                'time' => 1
            ]
        ];
    }

    private static function sqlGroupByMetadata(): string
    {
        return <<<'SQL'
select json_extract(tr.metadata, '$.category') as category,
       round(sum(julianday(tr.end) - julianday(tr.start)) * 24, 0) as time
from project p
         inner join task t on t.project_id = p.id
         left join tracking tr on tr.task_id = t.id
where json_extract(tr.metadata, '$.category') not null
group by json_extract(tr.metadata, '$.category');
SQL;
    }

    private static function expectedGroupByMetadata(): array
    {
        return [
            [
                'category' => 'dev',
                'time' => 7
            ],
            [
                'category' => 'meeting',
                'time' => 1
            ]
        ];
    }
}
