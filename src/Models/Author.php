<?php

namespace Src\Models;

use Sentience\DataLayer\Models\Attributes\Columns\AutoIncrement;
use Sentience\DataLayer\Models\Attributes\Columns\Column;
use Sentience\DataLayer\Models\Attributes\Relations\HasMany;
use Sentience\DataLayer\Models\Attributes\Table\PrimaryKeys;
use Sentience\DataLayer\Models\Attributes\Table\Table;
use Sentience\DataLayer\Models\Model;

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
}
