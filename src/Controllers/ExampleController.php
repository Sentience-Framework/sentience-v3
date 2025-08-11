<?php

declare(strict_types=1);

namespace Src\Controllers;

use Sentience\Abstracts\Controller;
use Sentience\Database\Database;
use Sentience\Database\Queries\Query;
use Sentience\Helpers\Json;
use Sentience\Sentience\Request;
use Sentience\Sentience\Response;
use Sentience\Sentience\Stdio;
use Src\Models\Migration;

class ExampleController extends Controller
{
    public function __construct(protected ?Request $request)
    {
    }

    public function cliExample(array $words, array $flags): void
    {
        Stdio::printLn(Json::encode([
            'words' => $words,
            'flags' => $flags
        ]));
    }

    public function jsonResponse(): void
    {
        Response::ok(['key' => 'value'], 'json');
    }

    public function xmlResponse(): void
    {
        Response::ok(['key' => 'value'], 'xml');
    }

    public function urlResponse(): void
    {
        Response::ok(['key' => 'value'], 'url');
    }

    public function getUser(): void
    {
        Response::ok($this->request);
    }

    public function getContact(): void
    {
        Response::ok($this->request);
    }

    public function getContacts(): void
    {
        Response::ok($this->request);
    }

    public function createContact(): void
    {
        Response::ok($this->request);
    }

    public function updateContact(): void
    {
        Response::ok($this->request);
    }

    public function query(Database $database): void
    {
        $startTime = microtime(true);

        $queries = [];

        $queries[] = $database->select(Query::alias(['public', 'table_1'], 'table1'))
            ->distinct()
            ->columns([
                'column1',
                Query::raw('CONCAT(column1, column2)'),
                Query::alias(
                    Query::raw('column2'),
                    'col2'
                )
            ])
            ->leftJoin(
                Query::alias('table2', 'jt'),
                'joinColumn',
                ['public', 't1'],
                't1Column'
            )
            ->rightJoin(
                Query::raw('table2'),
                'joinColumn',
                Query::raw('t1'),
                't1Column'
            )->innerJoin(
                ['public', 'table3'],
                'joinColumn',
                ['public', 'table1'],
                't1Column'
            )
            ->innerJoin(
                'table4',
                'joinColumn',
                'table1',
                't1Column'
            )
            ->join('RIGHT JOIN table2 jt ON jt.column1 = table1.column1 AND jt.column2 = table2.column2')
            ->whereEquals('column1', 10)
            ->whereGroup(fn($group) => $group->whereGreaterThanOrEquals('column2', 20)
                ->orwhereIsNull('column3'))
            ->where('DATE(`created_at`) > now()')
            ->whereGroup(fn($group) => $group->whereIn('column4', [1, 2, 3, 4])
                ->whereNotEquals('column5', 'test string'))
            ->whereGroup(fn($group) => $group)
            ->whereIn('column2', [])
            ->whereNotIn('column2', [])
            ->whereStartsWith('column2', 'a')
            ->whereEndsWith('column2', 'z')
            ->whereEmpty('empty_column')
            ->whereNotEmpty('not_empty_column')
            ->whereRegex('column6', 'file|read|write|open')
            ->whereNotRegex('column6', 'error')
            ->whereContains('column7', 'draft')
            ->groupBy([
                ['table', 'column'],
                'column2',
                Query::raw('rawColumn')
            ])
            ->having('COUNT(*) > ?', 10)
            ->orderByAsc('column4')
            ->orderByDesc('column5')
            ->orderByAsc(Query::raw('column6'))
            ->orderByDesc(Query::raw('column7'))
            ->limit(1)
            ->offset(10)
            ->toRawQuery();

        $queries[] = $database->insert(Query::alias('table_1', 'table1'))
            ->values([
                'column1' => Query::now(),
                'column2' => true,
                'column3' => false,
                'column4' => Query::raw('column1 + 1')
            ])
            // ->onConflictUpdate(['id'], [], 'id')
            ->onConflictIgnore(['id'], 'id')
            ->returning(['id'])
            ->toRawQuery();

        $queries[] = $database->update('table_1')
            ->values([
                'column1' => Query::now(),
                'column2' => true,
                'column3' => false,
                'column4' => Query::raw('column1 + 1')
            ])
            ->returning(['id'])
            ->toRawQuery();

        $queries[] = $database->delete('table_1')
            ->whereBetween('column2', 10, 20)
            ->orWhereNotBetween('column2', 70, 80)
            ->returning(['id'])
            ->toRawQuery();

        $queries[] = $database->createTable('table_1')
            ->ifNotExists()
            ->column('primary_key', 'int', true, null, true)
            ->column('column1', 'bigint', true)
            ->column('column2', 'varchar(255)')
            ->primaryKeys(['primary_key'])
            ->uniqueConstraint(['column1', 'column2'])
            ->foreignKeyConstraint('column1', 'table_2', 'reference_column', 'fk_table_1')
            ->toRawQuery();

        $queries[] = implode(
            ' ',
            $database->alterTable('table_1')
                ->addColumn('column3', 'INT')
                // ->alterColumn('column3', 'TEXT')
                ->renameColumn('column3', 'column4')
                ->dropColumn('column4')
                // ->addPrimaryKeys(['pk'])
                // ->addUniqueConstraint(['column1', 'column2'], 'unique_constraint')
                // ->addForeignKeyConstraint('column4', 'reference_table', 'reference_column')
                // ->dropConstraint('unique_constraint')
                ->toRawQuery()
        );

        $queries[] = $database->dropTable('table_1')
            ->ifExists()
            ->toRawQuery();

        foreach ($queries as $query) {
            Stdio::printLn($query);
            Stdio::printLn('');
        }

        $endTime = microtime(true);

        Stdio::printFLn('Time: %f', $endTime - $startTime);
    }

    public function select(Database $database): void
    {
        $start = microtime(true);

        $models = $database->selectModels(Migration::class)
            ->whereGreaterThanOrEquals('id', 10)
            ->execute();

        $migration = new Migration();
        $migration->batch = 1;
        $migration->filename = 'migration1' . microtime();
        $migration->appliedAt = Query::now();

        $migration2 = new Migration();
        $migration2->batch = 1;
        $migration2->filename = 'migration2' . microtime();
        $migration2->appliedAt = Query::now();

        $models = [$migration, $migration2];

        $database->insertModels($models)
            ->onDuplicateUpdate()
            ->execute();

        foreach ($models as $model) {
            $model->filename = md5((string) $model->id);
        }

        $database->updateModels($models)
            ->updateColumn('applied_at', Query::now())
            ->execute();

        // $database->deleteModels($models)->execute();

        echo json_encode($models, JSON_PRETTY_PRINT);

        $end = microtime(true);

        Stdio::printFLn('Time: %.2f ms', ($end - $start) * 1000);
    }
}
