<?php

namespace Src\Models;

use Sentience\ORM\Models\Attributes\Columns\AutoIncrement;
use Sentience\ORM\Models\Attributes\Columns\Column;
use Sentience\ORM\Models\Attributes\Relations\HasMany;
use Sentience\ORM\Models\Attributes\Table\PrimaryKeys;
use Sentience\ORM\Models\Attributes\Table\Table;
use Sentience\ORM\Models\Model;

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
