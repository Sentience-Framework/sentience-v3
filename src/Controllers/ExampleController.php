<?php

namespace Src\Controllers;

use stdClass;
use Sentience\Abstracts\Controller;
use Sentience\Database\Databases\SQLite\SQLiteDatabase;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Objects\HavingGroup;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\Query;
use Sentience\Exceptions\RelationException;
use Sentience\Helpers\Console;
use Sentience\Helpers\Json;
use Sentience\Mapper\Mapper;
use Sentience\ORM\Database\DB;
use Sentience\ORM\Database\Queries\SelectModelsQuery;
use Sentience\ORM\Models\Exceptions\CastException;
use Sentience\ORM\Models\Reflection\ReflectionModel;
use Sentience\Sentience\Request;
use Sentience\Sentience\Response;
use Sentience\Sentience\Stdio;
use Src\Models\Author;
use Src\Models\AuthorProfile;
use Src\Models\BadCastModel;
use Src\Models\Book;
use Src\Models\BookPublisher;
use Src\Models\Migration;
use Src\Models\Publisher;
use Src\Payloads\TestPayload;

class ExampleController extends Controller
{
    protected int $passed = 0;
    protected array $failures = [];
    protected array $queries = [];

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

        $queries[] = $db->selectSubQuery(
            $db->select('sub_table_1'),
            'table1'
        )
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
            )->leftJoinSubQuery(
                $db->select('sub_join_table'),
                'sjt',
                fn (Join $join): Join => $join->on(
                    ['innerjoin_table', 'join_column'],
                    ['on_table', 'on_column']
                )->whereBetween(['innerjoin_table', 'join_column'], 0, 9999)
            )
            // ->crossApplySubQuery(
            //     $db->select('sub_join_table'),
            //     'sjt',
            //     fn(Join $join): Join => $join->on(
            //         ['innerjoin_table', 'join_column'],
            //         ['on_table', 'on_column']
            //     )->whereBetween(['innerjoin_table', 'join_column'], 0, 9999)
            // )
            ->join('RIGHT JOIN table2 jt ON jt.column1 = table1.column1 AND jt.column2 = table2.column2')
            ->joinf('LATERAL LEFT JOIN lateral_table ON %.1f', 12.45)
            ->whereEquals('column1', 10)
            ->whereGroup(
                fn (WhereGroup $group) => $group
                    ->whereGreaterThanOrEquals('column2', 20)
                    ->orwhereIsNull('column3')
            )
            ->where('DATE(`created_at`) > :date OR DATE(`created_at`) < :date', [':date' => Query::now()])
            ->wheref('DATE(`updated_at`) > %s OR DATE(`updated_at`) < %s', Query::now(), Query::now())
            ->whereGroup(
                fn (WhereGroup $group) => $group
                    ->whereIn('column4', [1, 2, 3, 4])
                    ->whereNotEquals('column5', 'test string')
            )
            ->whereGroup(fn (WhereGroup $group) => $group)
            ->whereIn('column2', [])
            ->whereNotIn('column2', [])
            ->whereStartsWith('column2', 'a')
            ->whereEndsWith('column2', 'z')
            ->whereLike('column2', '%a%')
            ->whereNotLike('column2', '%z%')
            ->whereEmpty('empty_column')
            ->whereNotEmpty('not_empty_column')
            ->whereRegex('column6', 'file|read|write|open', 'i')
            ->whereNotRegex('column6', 'error')
            ->whereContains('column7', 'draft')
            ->whereOperator('columnOperator', '@>', 2)
            ->groupBy([
                ['table', 'column'],
                'column2',
                Query::raw('rawColumn')
            ])
            ->having('COUNT(*) > :count', [':count' => 10])
            ->havingIn('id', [1, 2, 3, 4])
            ->orHavingGroup(fn (HavingGroup $conditionGroup): HavingGroup => $conditionGroup->having('column1 = 1'))
            ->orderByAsc('column4')
            ->orderByDesc('column5')
            ->orderByAsc(Query::raw('column6'))
            ->orderByDesc(Query::raw('column7'))
            ->limit(1)
            ->offset(10)
            ->union($db->select('union'))
            ->unionAll($db->select('union_all'))
            ->toSql();

        $queries[] = $db->insert('table_1')
            ->values([
                'column1' => Query::now(),
                'column2' => true,
                'column3' => false,
                'column4' => Query::raw('column1 + 1')
            ])
            ->values([
                'column3' => false,
                'column4' => Query::raw('column1 + 1'),
                'column1' => Query::now()
            ])
            ->onConflictDoUpdate(['id'], [])
            // ->onConflictDoNothing(['id'])
            ->returning(['id'])
            ->lastInsertId('id')
            ->toSql();

        $queries[] = $db->update('table_1')
            ->set([
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
                    ->whereRegex('regexable_column', '[0-9]', 'im')
            )
            ->returning(['id'])
            ->toSql();

        $queries[] = $db->delete('table_1')
            ->whereBetween('column2', 10, 20)
            ->orWhereNotBetween('column2', 70, 80)
            ->whereLike('name', 'case_insensitive_like%', true)
            ->returning(['id'])
            ->toSql();

        $queries[] = $db->createTable('table_1')
            ->ifNotExists()
            ->column('primary_key', 'int', true, null, true)
            ->column('column1', 'bigint', true)
            ->column('column2', 'varchar(255)')
            ->primaryKeys(['primary_key'])
            ->uniqueConstraint(['column1', 'column2'])
            ->foreignKeyConstraint('column1', 'table_2', 'reference_column', 'fk_table_1', ReferentialActionEnum::NoAction)
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

        $queries[] = $db->createIndex('indexable_table', 'index')
            ->ifNotExists()
            ->unique()
            ->columns(['column1', 'column2'])
            ->toSql();

        $queries[] = $db->dropIndex('indexable_table', 'index')
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
                ->whereLike('filename', '%a%')
                ->whereGlob('filename', '*a*')
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
                // ->emulateUpsert(false)
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
        $db->transaction(
            function (DB $db): void {
                $db->exec('SELECT 1 -- First transaction');

                $db->transaction(
                    function (DB $db): void {
                        $db->exec('SELECT 1 -- Second transaction');

                        $db->transaction(
                            function (DB $db): void {
                                $db->exec('SELECT 1 -- Third transaction');

                                $db->transaction(
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
                null,
                ReferentialActionEnum::SetNull
            )
            ->execute();
    }

    public function relations(): void
    {
        $debug = function (string $query, float $start, ?string $error = null): void {
            $this->queries[] = $query;
        };

        $db = DB::connect(Driver::SQLite, ':memory:', null, [], [], $debug, true);

        $this->printSection('Schema');

        $this->queries = [];

        $db->createModel(Author::class)->execute();
        $db->createModel(AuthorProfile::class)->execute();
        $db->createModel(Book::class)->execute();
        $db->createModel(Publisher::class)->execute();
        $db->createModel(BookPublisher::class)->execute();

        $this->printQueries();

        $tolkien = new Author();
        $tolkien->name = 'Tolkien';

        $herbert = new Author();
        $herbert->name = 'Herbert';

        $nobody = new Author();
        $nobody->name = 'Nobody';

        $db->insertModels([$tolkien, $herbert, $nobody])->execute();

        $profile = new AuthorProfile();
        $profile->authorId = $tolkien->id;
        $profile->biography = 'Author of Middle-earth';
        $profile->links = ['https://www.tolkienestate.com', 'https://www.tolkiensociety.org'];
        $profile->preferences = (object) ['theme' => 'dark', 'perPage' => 25];
        $profile->tags = ['fantasy', 'philology'];

        $db->insertModels([$profile])->execute();

        $fellowship = new Book();
        $fellowship->name = 'The Fellowship of the Ring';
        $fellowship->authorId = $tolkien->id;

        $twoTowers = new Book();
        $twoTowers->name = 'The Two Towers';
        $twoTowers->authorId = $tolkien->id;

        $dune = new Book();
        $dune->name = 'Dune';
        $dune->authorId = $herbert->id;

        $db->insertModels([$fellowship, $twoTowers, $dune])->execute();

        $allenUnwin = new Publisher();
        $allenUnwin->name = 'Allen & Unwin';

        $chilton = new Publisher();
        $chilton->name = 'Chilton';

        $db->insertModels([$allenUnwin, $chilton])->execute();

        $fellowshipAllenUnwin = new BookPublisher();
        $fellowshipAllenUnwin->bookId = $fellowship->id;
        $fellowshipAllenUnwin->publisherId = $allenUnwin->id;

        $fellowshipChilton = new BookPublisher();
        $fellowshipChilton->bookId = $fellowship->id;
        $fellowshipChilton->publisherId = $chilton->id;

        $duneChilton = new BookPublisher();
        $duneChilton->bookId = $dune->id;
        $duneChilton->publisherId = $chilton->id;

        $db->insertModels([$fellowshipAllenUnwin, $fellowshipChilton, $duneChilton])->execute();

        $this->printSection('Fixtures');

        $this->printModels([
            'authors' => [$tolkien, $herbert, $nobody],
            'author_profiles' => [$profile],
            'books' => [$fellowship, $twoTowers, $dune],
            'publishers' => [$allenUnwin, $chilton],
            'book_publishers' => [$fellowshipAllenUnwin, $fellowshipChilton, $duneChilton]
        ]);

        $authors = $this->printRelations(
            'HasMany - Author->relation(\'books\')',
            fn (): array => $db->selectModels(Author::class)
                ->relation('books')
                ->execute()
        );

        $authorsByName = $this->indexByName($authors);

        $this->assert('Tolkien has 2 books', is_array($authorsByName['Tolkien']->books) && count($authorsByName['Tolkien']->books) == 2);
        $this->assert('Herbert has 1 book', is_array($authorsByName['Herbert']->books) && count($authorsByName['Herbert']->books) == 1);
        $this->assert('Nobody has 0 books', is_array($authorsByName['Nobody']->books) && count($authorsByName['Nobody']->books) == 0);

        $authors = $this->printRelations(
            'HasOne - Author->relation(\'profile\')',
            fn (): array => $db->selectModels(Author::class)
                ->relation('profile')
                ->execute()
        );

        $authorsByName = $this->indexByName($authors);

        $this->assert('Tolkien profile is loaded', $authorsByName['Tolkien']->profile instanceof AuthorProfile);
        $this->assert('Tolkien biography matches', $authorsByName['Tolkien']->profile?->biography == 'Author of Middle-earth');
        $this->assert('Herbert profile is null', $authorsByName['Herbert']->profile === null);
        $this->assert('single query, profile is joined', count($this->queries) == 1);

        $books = $this->printRelations(
            'BelongsTo - Book->relation(\'author\')',
            fn (): array => $db->selectModels(Book::class)
                ->relation('author')
                ->execute()
        );

        $booksByName = $this->indexByName($books);

        $this->assert('Fellowship author is Tolkien', $booksByName['The Fellowship of the Ring']->author?->name == 'Tolkien');
        $this->assert('Two Towers author is Tolkien', $booksByName['The Two Towers']->author?->name == 'Tolkien');
        $this->assert('Dune author is Herbert', $booksByName['Dune']->author?->name == 'Herbert');
        $this->assert('single query, author is joined', count($this->queries) == 1);

        $books = $this->printRelations(
            'ManyToMany - Book->relation(\'publishers\')',
            fn (): array => $db->selectModels(Book::class)
                ->relation('publishers')
                ->execute()
        );

        $booksByName = $this->indexByName($books);

        $this->assert('Fellowship has 2 publishers', count($booksByName['The Fellowship of the Ring']->publishers) == 2);
        $this->assert('Two Towers has 0 publishers', count($booksByName['The Two Towers']->publishers) == 0);
        $this->assert('Dune has 1 publisher', count($booksByName['Dune']->publishers) == 1);

        $publishers = $this->printRelations(
            'ManyToMany reversed - Publisher->relation(\'books\')',
            fn (): array => $db->selectModels(Publisher::class)
                ->relation('books')
                ->execute()
        );

        $publishersByName = $this->indexByName($publishers);

        $this->assert('Chilton has 2 books', count($publishersByName['Chilton']->books) == 2);
        $this->assert('Allen & Unwin has 1 book', count($publishersByName['Allen & Unwin']->books) == 1);

        $books = $this->printRelations(
            'Nested to-one - Book->relation(\'author->profile\')',
            fn (): array => $db->selectModels(Book::class)
                ->relation('author->profile')
                ->execute()
        );

        $booksByName = $this->indexByName($books);

        $this->assert('Fellowship author->profile is loaded', $booksByName['The Fellowship of the Ring']->author?->profile instanceof AuthorProfile);
        $this->assert('Dune author->profile is null', $booksByName['Dune']->author?->profile === null);
        $this->assert('single query, both levels are joined', count($this->queries) == 1);

        $authors = $this->printRelations(
            'Nested to-many - Author->relation(\'books->publishers\')',
            fn (): array => $db->selectModels(Author::class)
                ->relation('books->publishers')
                ->execute()
        );

        $authorsByName = $this->indexByName($authors);
        $tolkienBooksByName = $this->indexByName($authorsByName['Tolkien']->books);

        $this->assert('Tolkien has 2 books', count($authorsByName['Tolkien']->books) == 2);
        $this->assert('Fellowship has 2 nested publishers', count($tolkienBooksByName['The Fellowship of the Ring']->publishers) == 2);
        $this->assert('Two Towers has 0 nested publishers', count($tolkienBooksByName['The Two Towers']->publishers) == 0);
        $this->assert('4 queries, flat in row count', count($this->queries) == 4);

        $authors = $this->printRelations(
            'Nested to-one under to-many - Author->relation(\'books->author\')',
            fn (): array => $db->selectModels(Author::class)
                ->relation('books->author')
                ->execute()
        );

        $authorsByName = $this->indexByName($authors);

        $booksResolveToTolkien = true;

        foreach ($authorsByName['Tolkien']->books as $book) {
            if ($book->author?->name != 'Tolkien') {
                $booksResolveToTolkien = false;
            }
        }

        $this->assert('Tolkien books->author resolves back to Tolkien', $booksResolveToTolkien);
        $this->assert('2 queries, author joined into the books query', count($this->queries) == 2);

        $publishers = $this->printRelations(
            'Nested to-one under many-to-many - Publisher->relation(\'books->author\')',
            fn (): array => $db->selectModels(Publisher::class)
                ->relation('books->author')
                ->execute()
        );

        $publishersByName = $this->indexByName($publishers);

        $chiltonBooksResolveAuthor = count($publishersByName['Chilton']->books) == 2;

        foreach ($publishersByName['Chilton']->books as $book) {
            if (!($book->author instanceof Author)) {
                $chiltonBooksResolveAuthor = false;
            }
        }

        $this->assert('Chilton books->author resolved', $chiltonBooksResolveAuthor);

        $authors = $this->printRelations(
            'Relations with where - Author->relation(\'books\')->relation(\'profile\')->whereEquals(\'name\', \'Tolkien\')',
            fn (): array => $db->selectModels(Author::class)
                ->relation('books')
                ->relation('profile')
                ->whereEquals('name', 'Tolkien')
                ->execute()
        );

        $this->assert('exactly 1 model returned', count($authors) == 1);
        $this->assert('books populated', count($authors) == 1 && count($authors[0]->books) == 2);
        $this->assert('profile populated', count($authors) == 1 && $authors[0]->profile instanceof AuthorProfile);

        $authors = $this->printRelations(
            'Overlapping paths - Author->relation(\'books\')->relation(\'books\')->relation(\'books->author\')',
            fn (): array => $db->selectModels(Author::class)
                ->relation('books')
                ->relation('books')
                ->relation('books->author')
                ->execute()
        );

        $authorsByName = $this->indexByName($authors);

        $this->assert('Tolkien still has 2 books', count($authorsByName['Tolkien']->books) == 2);
        $this->assert('overlapping paths are not queried twice', count($this->queries) == 2);

        $authors = $this->printRelations(
            'Empty result - Author->relation(\'books\')->relation(\'profile\')->whereEquals(\'name\', \'No Such Author\')',
            fn (): array => $db->selectModels(Author::class)
                ->relation('books')
                ->relation('profile')
                ->whereEquals('name', 'No Such Author')
                ->execute()
        );

        $this->assert('empty array returned', count($authors) == 0);
        $this->assert('no relation queries issued', count($this->queries) == 1);

        $authors = $this->printRelations(
            'Callback on to-many - Author->relation(\'books\', fn ($query) => $query->whereEquals(\'name\', \'The Two Towers\'))',
            fn (): array => $db->selectModels(Author::class)
                ->relation(
                    'books',
                    fn (SelectModelsQuery $query): SelectModelsQuery => $query->whereEquals('name', 'The Two Towers')
                )
                ->execute()
        );

        $authorsByName = $this->indexByName($authors);

        $this->assert('Tolkien is filtered down to 1 book', count($authorsByName['Tolkien']->books) == 1);
        $this->assert('Herbert is filtered down to 0 books', count($authorsByName['Herbert']->books) == 0);
        $this->assert('the relation scope survives outside the callback group', str_contains($this->queries[1], 'IN (1, 2, 3) AND ('));

        $authors = $this->printRelations(
            'Callback with orWhere - the relation scope must not be escapable',
            fn (): array => $db->selectModels(Author::class)
                ->whereEquals('name', 'Herbert')
                ->relation(
                    'books',
                    fn (SelectModelsQuery $query): SelectModelsQuery => $query
                        ->whereEquals('name', 'Dune')
                        ->orWhereEquals('name', 'The Two Towers')
                )
                ->execute()
        );

        $this->assert('Herbert keeps only his own matching book', count($authors) == 1 && count($authors[0]->books) == 1);
        $this->assert('the borrowed book did not leak in', count($authors) == 1 && $authors[0]->books[0]->name == 'Dune');

        $books = $this->printRelations(
            'Callback on to-one - the condition lands in ON, so the LEFT JOIN is preserved',
            fn (): array => $db->selectModels(Book::class)
                ->relation(
                    'author',
                    fn (Join $join): Join => $join->whereEquals(['author', 'name'], 'Tolkien')
                )
                ->execute()
        );

        $booksByName = $this->indexByName($books);

        $this->assert('every book row is still returned', count($books) == 3);
        $this->assert('a matching author is still loaded', $booksByName['The Fellowship of the Ring']->author?->name == 'Tolkien');
        $this->assert('a filtered out author becomes null', $booksByName['Dune']->author === null);
        $this->assert('the condition is in the ON clause', str_contains($this->queries[0], 'ON ') && !str_contains($this->queries[0], 'WHERE "author"'));

        $authors = $this->printRelations(
            'Callbacks at both levels - a filtered to-many narrows what the next level looks up',
            fn (): array => $db->selectModels(Author::class)
                ->relations([
                    'profile',
                    'books' => fn (SelectModelsQuery $query): SelectModelsQuery => $query->whereEquals('name', 'The Fellowship of the Ring'),
                    'books->publishers' => fn (SelectModelsQuery $query): SelectModelsQuery => $query->whereEquals('name', 'Chilton')
                ])
                ->execute()
        );

        $authorsByName = $this->indexByName($authors);
        $tolkienBooksByName = $this->indexByName($authorsByName['Tolkien']->books);

        $this->assert('Tolkien keeps 1 book', count($authorsByName['Tolkien']->books) == 1);
        $this->assert('that book keeps 1 publisher', count($tolkienBooksByName['The Fellowship of the Ring']->publishers) == 1);
        $this->assert('the profile join is unaffected', $authorsByName['Tolkien']->profile instanceof AuthorProfile);
        $this->assert('the pivot lookup only covers the surviving book', str_contains($this->queries[2], 'IN (1)'));

        $authors = $this->printRelations(
            'Relation declared inside a callback - Author->relation(\'books\', fn ($query) => $query->relation(\'author\'))',
            fn (): array => $db->selectModels(Author::class)
                ->relation(
                    'books',
                    fn (SelectModelsQuery $query): SelectModelsQuery => $query->relation('author')
                )
                ->execute()
        );

        $authorsByName = $this->indexByName($authors);

        $this->assert('the nested relation was loaded', $authorsByName['Tolkien']->books[0]->author?->name == 'Tolkien');
        $this->assert('it was joined, not queried separately', count($this->queries) == 2);

        $this->printSection('Casts - what is actually stored');

        $this->queries = [];

        $rows = $db->select('author_profiles')
            ->columns(['links' => 'links', 'preferences' => 'preferences', 'tags' => 'tags'])
            ->execute()
            ->fetchAssocs();

        $this->printQueries();
        $this->printModels($rows);

        $this->assert('a Json array column is stored as a json array', $rows[0]['links'] == '["https:\\/\\/www.tolkienestate.com","https:\\/\\/www.tolkiensociety.org"]');
        $this->assert('a Json object column is stored as a json object', $rows[0]['preferences'] == '{"theme":"dark","perPage":25}');
        $this->assert('a Cast column is stored with its own codec', $rows[0]['tags'] == serialize(['fantasy', 'philology']));

        $profiles = $this->printRelations(
            'Casts - decoding on read',
            fn (): array => $db->selectModels(AuthorProfile::class)->execute()
        );

        $this->assert('a Json array column decodes to an array', $profiles[0]->links == ['https://www.tolkienestate.com', 'https://www.tolkiensociety.org']);
        $this->assert('a Json object column decodes to an object', $profiles[0]->preferences instanceof stdClass);
        $this->assert('a Json object column keeps its values', $profiles[0]->preferences->theme == 'dark');
        $this->assert('a Cast column decodes with its own codec', $profiles[0]->tags == ['fantasy', 'philology']);

        $authors = $this->printRelations(
            'Casts - decoding through a joined relation',
            fn (): array => $db->selectModels(Author::class)
                ->relation('profile')
                ->whereEquals('name', 'Tolkien')
                ->execute()
        );

        $this->assert('a Json column decodes when its model arrives via a LEFT JOIN', $authors[0]->profile?->links == ['https://www.tolkienestate.com', 'https://www.tolkiensociety.org']);
        $this->assert('a Cast column decodes when its model arrives via a LEFT JOIN', $authors[0]->profile?->tags == ['fantasy', 'philology']);

        $this->printSection('Casts - round trip through an update');

        $profile = $profiles[0];
        $profile->links = ['https://www.tolkienestate.com'];
        $profile->preferences = (object) ['theme' => 'light', 'perPage' => 50];
        $profile->tags = ['fantasy'];

        $this->queries = [];

        $db->updateModels([$profile])->execute();

        $this->printQueries();

        $reread = $db->selectModels(AuthorProfile::class)->whereEquals('id', $profile->id)->execute();

        $this->printModels($reread);

        $this->assert('the updated array survives the round trip', $reread[0]->links == ['https://www.tolkienestate.com']);
        $this->assert('the updated object survives the round trip', $reread[0]->preferences->theme == 'light');
        $this->assert('the updated Cast column survives the round trip', $reread[0]->tags == ['fantasy']);

        $this->printSection('Casts - Json on an unsupported type throws');

        try {
            (new ReflectionModel(BadCastModel::class))->getProperty('name')->getCast();

            $this->assert('Json on a string property throws CastException', false);
        } catch (CastException $exception) {
            Stdio::printFLn('%s', $exception->getMessage());

            $this->assert('Json on a string property throws CastException', true);
        }

        $this->printSection('Unknown relations');

        foreach (['nope', 'books->nope'] as $relation) {
            try {
                $db->selectModels(Author::class)
                    ->relation($relation)
                    ->execute();

                $this->assert(sprintf('relation(\'%s\') throws RelationException', $relation), false);
            } catch (RelationException $exception) {
                Stdio::printFLn('-- relation(\'%s\')', $relation);
                Stdio::printFLn('%s', $exception->getMessage());

                $this->assert(sprintf('relation(\'%s\') throws RelationException', $relation), true);
            }
        }

        $this->printSection('Update and delete with relations loaded');

        $authors = $db->selectModels(Author::class)
            ->relation('books')
            ->relation('profile')
            ->whereEquals('name', 'Herbert')
            ->execute();

        $author = $authors[0];
        $author->name = 'Frank Herbert';

        $authorCount = count($db->selectModels(Author::class)->execute());

        $this->queries = [];

        $db->updateModels([$author])->execute();
        $db->deleteModels([$author])->execute();

        $this->printQueries();
        $this->printModels($db->selectModels(Author::class)->execute());

        $this->assert('relation properties do not leak into the UPDATE', !str_contains($this->queries[0], 'books'));
        $this->assert('relation properties do not leak into the DELETE', !str_contains($this->queries[1], 'books'));
        $this->assert('the row is gone', count($db->selectModels(Author::class)->execute()) == $authorCount - 1);

        $this->printSection('Summary');

        Stdio::printFLn('%d passed, %d failed', $this->passed, count($this->failures));

        foreach ($this->failures as $failure) {
            Stdio::errorFLn('[FAIL] %s', $failure);
        }
    }

    protected function printSection(string $title): void
    {
        $equalSigns = ((Console::getWidth() - strlen($title)) / 2) - 1;

        Stdio::print(PHP_EOL);
        Stdio::printFLn(
            '%s %s %s',
            str_repeat('=', (int) max(3, ceil($equalSigns))),
            $title,
            str_repeat('=', (int) max(3, floor($equalSigns)))
        );
    }

    protected function printQueries(): void
    {
        foreach ($this->queries as $query) {
            Stdio::printFLn('-- %s', $query);
        }
    }

    protected function printModels(array $models): void
    {
        Stdio::printLn(Json::encode($models, JSON_PRETTY_PRINT));
    }

    protected function printRelations(string $title, callable $callback): array
    {
        $this->printSection($title);

        $this->queries = [];

        $models = $callback();

        $this->printQueries();
        $this->printModels($models);

        return $models;
    }

    protected function assert(string $description, bool $condition): void
    {
        if (!$condition) {
            $this->failures[] = $description;

            Stdio::errorFLn('[FAIL] %s', $description);

            return;
        }

        $this->passed++;

        Stdio::printFLn('[PASS] %s', $description);
    }

    protected function indexByName(array $models): array
    {
        $indexed = [];

        foreach ($models as $model) {
            $indexed[$model->name] = $model;
        }

        return $indexed;
    }

    public function table(DB $db): void
    {
        $inMemoryDatabase = SQLiteDatabase::memory();

        $inMemoryDatabase->table('migrations_foreign')
            ->create()
            ->identity('id')
            ->int('batch')
            ->string('name')
            ->dateTime('applied_datetime')
            ->execute();

        $inMemoryDatabase->table('migrations_foreign')
            ->insert([
                'batch' => 10,
                'name' => '<foreign database table record>',
                'applied_datetime' => Query::now()
            ])
            ->execute();

        $foreignTable = $inMemoryDatabase->table('migrations_foreign');

        $columns = $db->table('migrations')->copyFrom($foreignTable);

        // fn(array $values): array => [
        //     'batch' => $values['batch'],
        //     'filename' => $values['name'],
        //     'applied_at' => $values['applied_datetime']
        // ]

        print_r($columns);
    }
}
