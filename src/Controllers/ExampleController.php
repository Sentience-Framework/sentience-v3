<?php

namespace Src\Controllers;

use Sentience\Abstracts\Controller;
use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Query;
use Sentience\Helpers\Json;
use Sentience\Mapper\Mapper;
use Sentience\ORM\Database\DB;
use Sentience\Sentience\Request;
use Sentience\Sentience\Response;
use Sentience\Sentience\Stdio;
use Src\Models\Author;
use Src\Models\Book;
use Src\Models\Migration;
use Src\Payloads\TestPayload;

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

    public function query(DB $db): void
    {
        $start = microtime(true);

        $queries = [];

        $queries[] = $db->select(['public', 'table_1'], 'table1')
            ->distinct()
            ->columns([
                'column1',
                Query::raw('CONCAT(column1, column2)'),
                'col2' => Query::raw('column2HERE')
            ])
            ->leftJoin(
                'leftjoin_table',
                fn (Join $join): Join => $join->on(
                    ['leftjoin_table', 'join_column'],
                    ['on_table', 'on_column']
                )
            )->innerJoin(
                'innerjoin_table',
                fn (Join $join): Join => $join->on(
                    ['innerjoin_table', 'join_column'],
                    ['on_table', 'on_column']
                )->whereBetween(['innerjoin_table', 'join_column'], 0, 9999)
            )
            ->join('RIGHT JOIN table2 jt ON jt.column1 = table1.column1 AND jt.column2 = table2.column2')
            ->whereEquals('column1', 10)
            ->whereGroup(
                fn ($group) => $group
                    ->whereGreaterThanOrEquals('column2', 20)
                    ->orwhereIsNull('column3')
            )
            ->where('DATE(`created_at`) > :date OR DATE(`created_at`) < :date', [':date' => Query::now()])
            ->whereGroup(
                fn ($group) => $group
                    ->whereIn('column4', [1, 2, 3, 4])
                    ->whereNotEquals('column5', 'test string')
            )
            ->whereGroup(fn ($group) => $group)
            ->whereIn('column2', [])
            ->whereNotIn('column2', [])
            ->whereStartsWith('column2', 'a')
            ->whereEndsWith('column2', 'z')
            ->whereLike('column2', '%a%')
            ->whereNotLike('column2', '%z%')
            ->whereEmpty('empty_column')
            ->whereNotEmpty('not_empty_column')
            ->whereRegex('column6', '/file|read|write|open/i')
            ->whereNotRegex('column6', 'error')
            ->whereContains('column7', 'draft')
            ->groupBy([
                ['table', 'column'],
                'column2',
                Query::raw('rawColumn')
            ])
            ->having('COUNT(*) > :count', [':count' => 10])
            ->orderByAsc('column4')
            ->orderByDesc('column5')
            ->orderByAsc(Query::raw('column6'))
            ->orderByDesc(Query::raw('column7'))
            ->limit(1)
            ->offset(10)
            ->toSql();

        $queries[] = $db->insert('table_1')
            ->values([
                'column1' => Query::now(),
                'column2' => true,
                'column3' => false,
                'column4' => Query::raw('column1 + 1')
            ])
            ->onConflictUpdate(['id'], [])
            // ->onConflictIgnore(['id'])
            ->returning(['id'])
            ->lastInsertId('id')
            ->toSql();

        $queries[] = $db->update('table_1')
            ->values([
                'column1' => Query::now(),
                'column2' => true,
                'column3' => false,
                'column4' => Query::raw('column1 + 1')
            ])
            ->whereExists($db->select('sub_table_1')
                ->columns([
                    'id',
                    'name',
                    'created_at',
                    'updated_at'
                ])
                ->whereIn(
                    'id',
                    $db->select('sub_sub_table_1')
                        ->columns(['id'])
                        ->whereEquals('deleted_at', null)
                ))
            ->orWhereLessThanOrEquals(
                'count',
                $db->select('sub_table_2')
                    ->columns([
                        Query::raw('MAX(id)')
                    ])
                    ->whereBetween('column_5', 1, 2)
                    ->whereRegex('regexable_column', '/[0-9]/im')
            )
            ->returning(['id'])
            ->toSql();

        $queries[] = $db->delete('table_1')
            ->whereBetween('column2', 10, 20)
            ->orWhereNotBetween('column2', 70, 80)
            ->returning(['id'])
            ->toSql();

        $queries[] = $db->createTable('table_1')
            ->ifNotExists()
            ->column('primary_key', 'int', true, null, true)
            ->column('column1', 'bigint', true)
            ->column('column2', 'varchar(255)')
            ->primaryKeys(['primary_key'])
            ->uniqueConstraint(['column1', 'column2'])
            ->foreignKeyConstraint('column1', 'table_2', 'reference_column', 'fk_table_1', [ReferentialActionEnum::ON_UPDATE_NO_ACTION])
            ->constraint('UNIQUE "test" COLUMNS ("column1", "column2")')
            ->toSql();

        $queries[] = implode(
            PHP_EOL,
            $db->alterTable('table_1')
                ->addColumn('column3', 'INT')
                ->addColumn('columnDateTimeFunc', 'DATETIME', true, Query::raw('now()'))
                // ->alterColumn('column3', 'TEXT AUTO_INCREMENT')
                ->renameColumn('column3', 'column4')
                ->dropColumn('column4')
                ->alter('ADD COLUMN id BIGINT REFERENCES table(id)')
                // ->addPrimaryKeys(['pk'])
                // ->addUniqueConstraint(['column1', 'column2'], 'unique_constraint')
                // ->addForeignKeyConstraint('column4', 'reference_table', 'reference_column')
                // ->dropConstraint('unique_constraint')
                ->toSql()
        );

        $queries[] = $db->dropTable('table_1')
            ->ifExists()
            ->toSql();

        foreach ($queries as $query) {
            Stdio::printLn($query);
            Stdio::printLn('');
        }

        $end = microtime(true);

        Stdio::printFLn('Time: %f', $end - $start);
    }

    public function crud(DB $db): void
    {
        $emulatePrepare = config('database->emulate_prepares');

        $start = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $models = [];

            $selectedModels = $db->selectModels(Migration::class)
                ->whereGreaterThanOrEquals('id', 0)
                ->execute();

            array_push($models, ...$selectedModels);

            $migration = new Migration();
            $migration->batch = 1;
            $migration->filename = 'migration1' . microtime();
            $migration->appliedAt = now();

            $migration2 = new Migration();
            $migration2->batch = 1;
            $migration2->filename = 'migration2' . microtime() . '1';
            $migration2->appliedAt = now();

            // breakpoint(get_defined_vars(), fn($var) => json_encode($var));

            $insertedModels = [$migration, $migration2];

            $db->insertModels($insertedModels)
                ->onDuplicateUpdate()
                ->emulateUpsert(false)
                ->execute($emulatePrepare);

            array_push($models, ...$insertedModels);

            foreach ($models as $model) {
                $model->filename = md5((string) $model->id);
            }

            $db->updateModels($models)
                ->updateColumn('applied_at', Query::now())
                ->execute($emulatePrepare);

            $db->deleteModels($models)
                ->execute($emulatePrepare);

            // $db->prepared(
            //     'SELECT * FROM migrations -- test comment with a ? item
            //     WHERE id > ? AND filename = ?
            //     -- Hoi dit is een test
            //     OR filename = \'\'\'\'\'""""\'\'#test /* test */ --hoi \'
            //     AND filename = ?;',
            //     [
            //         1,
            //         '\\\"\"\"\\\'\'',
            //         'test'
            //     ],
            //     $emulatePrepare
            // );

            // $db->prepared(
            //     'SELECT * FROM migrations /* Random :comment comment */ WHERE id > :id AND filename = \'#hoi\'
            //     OR filename = :filename',
            //     [
            //         ':id' => 2,
            //         ':filename' => '\\\"\"\"\\\'\''
            //     ],
            //     $emulatePrepare
            // );
        }

        $end = microtime(true);

        echo json_encode($models, JSON_PRETTY_PRINT);

        Stdio::printFLn('Time: %.2f ms', ($end - $start) * 1000);
    }

    public function select(DB $db): void
    {
        $db->createModel(Book::class)->ifNotExists()->execute();
        $db->createModel(Author::class)->ifNotExists()->execute();

        $models = $db->selectModels(Author::class)
            ->relation('books')
            ->execute();

        print_r($models);
    }

    public function transactions(DB $db): void
    {
        $db->transactionInCallback(
            function (DB $db): void {
                $db->exec('SELECT 1 -- First transaction');

                $db->transactionInCallback(
                    function (DB $db): void {
                        $db->exec('SELECT 1 -- Second transaction');

                        $db->transactionInCallback(
                            function (DB $db): void {
                                $db->exec('SELECT 1 -- Third transaction');

                                $db->transactionInCallback(
                                    function (DB $db): void {
                                        $db->exec('SELECT 1 -- Fourth transaction');
                                    }
                                );
                            }
                        );
                    }
                );
            }
        );
    }

    public function mapper(): void
    {
        $json = '[
        {
    "id": 1,
    "name": "name",
    "nested_object": {
        "nested_id": 1,
        "nested_name": "nested name"
    },
    "nested_objects":[
        {
            "nested_id": 1,
            "nested_name": "nested name"
        },
        {
            "nested_id": 2,
            "nested_name": "nested name"
        },
        {
            "nested_id": 3,
            "nested_name": "nested name"
        },
        {
            "nested_id": 4,
            "nested_name": "nested name"
        }
    ]
}]
        ';

        $mappedObjects = Mapper::toObject(Json::decode($json, false), TestPayload::class);

        echo 'Objects:';
        print_r($mappedObjects);

        echo PHP_EOL;

        echo 'Json:';
        print_r(json_encode($mappedObjects));
    }

    public function fk(DB $db): void
    {
        $db->dropTable('books')->ifExists()->execute();
        $db->dropTable('authors')->ifExists()->execute();

        $db->createTable('authors')
            ->column('id', 'INTEGER')
            ->column('name', 'TEXT')
            ->primaryKeys('id')
            ->execute();

        $db->createTable('books')
            ->column('id', 'INTEGER')
            ->column('name', 'TEXT')
            ->column('author_id', 'INTEGER')
            ->primaryKeys('id')
            ->foreignKeyConstraint(
                'author_id',
                'authors',
                'id',
                'author_fk',
                [ReferentialActionEnum::ON_DELETE_SET_NULL]
            )
            ->execute();
    }
}
