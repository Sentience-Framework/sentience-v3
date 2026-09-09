# \Sentience\ORM\Database\DB
```php
// Extends \Sentience\Database\Database, everything in sentience-database.md applies

DB->selectModels(string $model): \Sentience\ORM\Database\Queries\SelectModelsQuery
DB->insertModels(array|\Sentience\ORM\Models\Model $models): \Sentience\ORM\Database\Queries\InsertModelsQuery
DB->updateModels(array|\Sentience\ORM\Models\Model $models): \Sentience\ORM\Database\Queries\UpdateModelsQuery
DB->deleteModels(array|\Sentience\ORM\Models\Model $models): \Sentience\ORM\Database\Queries\DeleteModelsQuery
DB->createModel(string $model): \Sentience\ORM\Database\Queries\CreateModelQuery
DB->alterModel(string $model): \Sentience\ORM\Database\Queries\AlterModelQuery
DB->dropModel(string $model): \Sentience\ORM\Database\Queries\DropModelQuery
DB->cache(callable $store, callable $retrieve): static
```

# \Sentience\ORM\Models\Model
```php
Model::getTable(): string
Model::getColumns(): array
Model->jsonSerialize(): array
```

# Model attributes
```php
// Table-level, on the class

#[Table(string $table, ?string $alias = null)]
#[PrimaryKeys(array $columns)]
#[UniqueConstraint(array $columns, ?string $name = null)]

// Column-level, on a property

#[Column(string $column, mixed $default = null)]
#[AutoIncrement]
#[Cast(string|array $encode, string|array $decode)]
#[Json]
```

A property without a relation attribute is treated as a column. `#[Column]` is optional:
the column name defaults to the snake_cased property name. `#[Table]` is optional too: the
table name defaults to the pluralized, snake_cased class short name.

# Casts
```php
#[Cast(string|array $encode, string|array $decode)]
#[Json]
```

`#[Cast]` converts a property between its PHP value and its stored value. `$encode` runs on
the way into the database, `$decode` on the way out.

PHP attribute arguments must be constant expressions, so a closure cannot be passed here.
Both arguments take a callable that *is* a constant expression — a function name, or a
`[Class::class, 'method']` pair pointing at a public static method:

```php
#[Column('tags')]
#[Cast('serialize', 'unserialize')]
public array $tags;

#[Column('settings')]
#[Cast([Settings::class, 'toJson'], [Settings::class, 'fromJson'])]
public Settings $settings;
```

A non-callable value throws `\Sentience\ORM\Models\Exceptions\CastException` as soon as the
attribute is read.

`#[Json]` is the built-in cast for `array` and `object` properties, and needs no arguments:

```php
#[Column('links')]
#[Json]
public array $links;

#[Column('preferences')]
#[Json]
public object $preferences;
```

An `array` property decodes to an associative array, an `object` property to a `stdClass`.
On any other property type `#[Json]` throws `CastException` — use `#[Cast]` for those.

How casts behave:

- `$encode` is applied by `insertModels`, `updateModels` and `deleteModels`, and to a
  `#[Column]` default by `createModel` and `alterModel`.
- `$decode` is applied by `selectModels`, including when the model arrives as an eager
  loaded relation.
- `null` is passed straight through in both directions; neither callable is invoked for it.
- A cast replaces the built-in type conversion, so `bool`, `int`, `float`, `string`,
  `DateTime`, `DateTimeImmutable`, `Timestamp` and `BackedEnum` handling do not run on a
  cast property.
- A cast column is created as a text column, because what is stored is the encoded value.
- Casts do **not** apply to `where*` values. A condition compares against the stored
  representation, so encode it yourself: `->whereEquals('tags', serialize(['fantasy']))`.

# Relations
```php
#[HasOne(string $model, string $mToRJoin)]
#[BelongsTo(string $model, string $mToRJoin)]
#[HasMany(string $model, string $mToRJoin)]
#[ManyToMany(string $model, string $mToRJoin, string $pivot)]
```

`$mToRJoin` is a DSL string joining a property on this model to a property on the related
model. Both identifiers are PHP **property** names (not columns), and the arrow points at
the side that holds the foreign key.

| Attribute | `$mToRJoin` example | FK lives on | cardinality | how it's loaded |
|---|---|---|---|---|
| `HasOne` | `'id->authorId'` | related table | to-one | `LEFT JOIN` |
| `BelongsTo` | `'authorId<-id'` | this table | to-one | `LEFT JOIN` |
| `HasMany` | `'id-<authorId'` | related table | to-many | separate `WHERE IN` query |
| `ManyToMany` | `'id-<bookId:publisherId>-id'` | `$pivot` model's table | to-many | separate pivot query, then a `WHERE IN` query |

`ManyToMany`'s join string encodes both sides of the pivot: `'id-<bookId:publisherId>-id'`
reads as this model's `id` maps to the pivot's `bookId`, and the pivot's `publisherId` maps
to the related model's `id`.

Rules a model must follow:
- to-one relation properties must be nullable (e.g. `?Author $author`) — a `LEFT JOIN` with
  no match assigns `null`.
- to-many relation properties are typed `array` — assigned `[]` when nothing matches.

Relation properties are never treated as columns: they are skipped by `createModel`,
`alterModel`, `insertModels`, `updateModels` and `Model::getColumns()`.

# \Sentience\ORM\Database\Queries\SelectModelsQuery
```php
// Same where*/orWhere* surface as \Sentience\Database\Queries\Traits\WhereTrait (see sentience-database.md)

SelectModelsQuery->relation(string $relation, ?callable $callback = null): static
SelectModelsQuery->relations(array $relations): static
SelectModelsQuery->distinct(): static
SelectModelsQuery->orderByAsc(\Sentience\Database\Queries\Interfaces\Sql|array|string $column): static
SelectModelsQuery->orderByDesc(\Sentience\Database\Queries\Interfaces\Sql|array|string $column): static
SelectModelsQuery->limit(int $limit): static
SelectModelsQuery->offset(int $offset): static
SelectModelsQuery->execute(bool $emulatePrepare = false): array
```

`relation()` takes a relation **property** name. Nested relations are separated by `->`,
e.g. `->relation('books->publishers')`. Requesting a nested path implies its parents
(`->relation('books->publishers')` also loads `books`), and repeating a prefix across calls
does not duplicate work (`->relation('books')->relation('books->publishers')` still loads
`books` once). An unknown property throws `\Sentience\Exceptions\RelationException`.

`relations()` takes either a list of paths, a map of path to callback, or a mix of both:

```php
->relations([
    'profile',
    'books' => fn (SelectModelsQuery $query): SelectModelsQuery => $query->orderByAsc('name')
])
```

# Modifying a relation query

`relation()` takes an optional callback that receives the builder the relation is actually
loaded with. Which builder that is depends on the cardinality, because to-one and to-many
relations are loaded differently:

```php
// to-many (HasMany, ManyToMany) -> the relation's own SelectModelsQuery
$db->selectModels(Author::class)
    ->relation('books', fn (SelectModelsQuery $query): SelectModelsQuery => $query->whereIsNull('deleted_at'))
    ->execute();

// to-one (HasOne, BelongsTo) -> the \Sentience\Database\Queries\Objects\Join
$db->selectModels(Book::class)
    ->relation('author', fn (Join $join): Join => $join->whereIsNull(['author', 'deleted_at']))
    ->execute();
```

Both objects expose the same `where*`/`orWhere*` surface, so a plain
`fn ($query) => $query->whereIsNull('deleted_at')` works on either. The difference is where
the condition ends up:

- to-many: in the relation query's `WHERE`, alongside `orderByAsc`, `orderByDesc`, `limit`,
  `offset`, `distinct` and further `relation()` calls.
- to-one: in the `ON` clause of the `LEFT JOIN`, not the `WHERE`. This is deliberate — a
  condition in the `WHERE` would drop the parent rows whose relation does not match, turning
  the outer join into an inner one. Filtered-out to-one relations come back as `null` and the
  parent row survives.

The `LEFT JOIN` alias of a to-one relation is its relation path, so qualify columns with it
(`['author', 'deleted_at']`, or `['author->profile', 'deleted_at']` for a nested path) when
the name also exists on another joined table.

Callbacks attach to the last segment of the path, so each level is targeted separately:

```php
$db->selectModels(Author::class)
    ->relation('books', fn (SelectModelsQuery $query): SelectModelsQuery => $query->whereEquals('name', 'Dune'))
    ->relation('books->publishers', fn (SelectModelsQuery $query): SelectModelsQuery => $query->whereEquals('name', 'Chilton'))
    ->execute();
```

Filtering a level also narrows what the next level looks up: only the surviving books' ids
reach the pivot query.

Two things to know:

- An `orWhere*` in a callback cannot widen the relation's own scope. The parent-key
  `IN (...)` is emitted as its own top-level condition and the callback's conditions are
  wrapped in a group, so you get `WHERE "books"."author_id" IN (1, 2) AND (... OR ...)`.
- `limit()` on a to-many callback limits the relation query as a whole, not per parent row,
  because all parents are loaded in one query.

# \Sentience\ORM\Database\Queries\InsertModelsQuery
```php
InsertModelsQuery->onDuplicateIgnore(): static
InsertModelsQuery->onDuplicateUpdate(array $excludeColumns = []): static
InsertModelsQuery->emulateUpsert(bool $inTransaction, bool $emulateReturning = true): static
InsertModelsQuery->execute(bool $emulatePrepare = false): array
```

# \Sentience\ORM\Database\Queries\UpdateModelsQuery
```php
// Same where*/orWhere* surface as \Sentience\Database\Queries\Traits\WhereTrait (see sentience-database.md)

UpdateModelsQuery->updateColumns(array $values): static
UpdateModelsQuery->updateColumn(string $column, null|bool|int|float|string|\DateTimeInterface $value): static
UpdateModelsQuery->execute(bool $emulatePrepare = false): array
```

# \Sentience\ORM\Database\Queries\DeleteModelsQuery
```php
// Same where*/orWhere* surface as \Sentience\Database\Queries\Traits\WhereTrait (see sentience-database.md)

DeleteModelsQuery->execute(bool $emulatePrepare = false): array
```

# Schema queries
```php
// \Sentience\ORM\Database\Queries\CreateModelQuery
CreateModelQuery->ifNotExists(): static
CreateModelQuery->execute(bool $emulatePrepare = false): null

// \Sentience\ORM\Database\Queries\AlterModelQuery
AlterModelQuery->execute(bool $emulatePrepares = false): null

// \Sentience\ORM\Database\Queries\DropModelQuery
DropModelQuery->ifExists(): static
DropModelQuery->execute(bool $emulatePrepare = false): null
```

# Model example
```php
#[Table('authors')]
#[PrimaryKeys(['id'])]
class Author extends Model
{
    #[Column('id')]
    #[AutoIncrement]
    public int $id;

    #[Column('name')]
    public string $name;

    #[HasMany(Book::class, 'id-<authorId')]
    public array $books;

    #[HasOne(AuthorProfile::class, 'id->authorId')]
    public ?AuthorProfile $profile;
}

#[Table('author_profiles')]
#[PrimaryKeys(['id'])]
class AuthorProfile extends Model
{
    #[Column('id')]
    #[AutoIncrement]
    public int $id;

    #[Column('author_id')]
    public int $authorId;

    #[Column('biography')]
    public string $biography;

    #[Column('links')]
    #[Json]
    public array $links;

    #[Column('preferences')]
    #[Json]
    public object $preferences;

    #[Column('tags')]
    #[Cast('serialize', 'unserialize')]
    public array $tags;

    #[BelongsTo(Author::class, 'authorId<-id')]
    public ?Author $author;
}

#[Table('books')]
#[PrimaryKeys(['id'])]
class Book extends Model
{
    #[Column('id')]
    #[AutoIncrement]
    public int $id;

    #[Column('name')]
    public string $name;

    #[Column('author_id')]
    public int $authorId;

    #[BelongsTo(Author::class, 'authorId<-id')]
    public ?Author $author;

    #[ManyToMany(Publisher::class, 'id-<bookId:publisherId>-id', BookPublisher::class)]
    public array $publishers;
}

#[Table('publishers')]
#[PrimaryKeys(['id'])]
class Publisher extends Model
{
    #[Column('id')]
    #[AutoIncrement]
    public int $id;

    #[Column('name')]
    public string $name;

    #[ManyToMany(Book::class, 'id-<publisherId:bookId>-id', BookPublisher::class)]
    public array $books;
}

#[Table('book_publishers')]
#[PrimaryKeys(['id'])]
class BookPublisher extends Model
{
    #[Column('id')]
    #[AutoIncrement]
    public int $id;

    #[Column('book_id')]
    public int $bookId;

    #[Column('publisher_id')]
    public int $publisherId;
}
```

# Select with relations example
```php
$authors = $db->selectModels(Author::class)
    ->relation('profile')
    ->relation('books->publishers')
    ->whereLike('name', '%tolkien%', true)
    ->orderByAsc('name')
    ->limit(10)
    ->execute();

$authors[0]->profile->biography;
$authors[0]->books[0]->publishers[0]->name;
```

## Queries emitted
```sql
-- to-one relation (profile) is LEFT JOINed into the main query
SELECT "authors"."id" AS "id", "authors"."name" AS "name", "profile"."id" AS "profile->id", "profile"."author_id" AS "profile->author_id", "profile"."biography" AS "profile->biography" FROM "authors" LEFT JOIN "author_profiles" AS "profile" ON "profile"."author_id" = "authors"."id" WHERE ("name" LIKE '%tolkien%') ORDER BY "name" ASC LIMIT 10

-- to-many relation (books), one WHERE IN query keyed on the parent authors' ids
SELECT "books"."id" AS "id", "books"."name" AS "name", "books"."author_id" AS "author_id" FROM "books" WHERE ("books"."author_id" IN (1))

-- many-to-many relation (publishers), pivot lookup query
SELECT "book_id" AS "book_id", "publisher_id" AS "publisher_id" FROM "book_publishers" WHERE "book_id" IN (1)

-- many-to-many relation (publishers), WHERE IN query keyed on the pivot's related ids
SELECT "publishers"."id" AS "id", "publishers"."name" AS "name" FROM "publishers" WHERE ("publishers"."id" IN (1))
```

# Query count
A query is issued per to-many relation level in the requested path, plus one extra query
per many-to-many pivot. To-one relations (`HasOne`, `BelongsTo`) cost nothing extra: they
are `LEFT JOIN`ed straight into their parent's query. In the example above, `profile` (to-one)
adds no query; `books` (to-many) adds one; `publishers` (many-to-many, nested under `books`)
adds two — one for the pivot table, one for the related `publishers` rows.
