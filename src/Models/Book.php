<?php

namespace Src\Models;

use Sentience\DataLayer\Models\Attributes\Columns\AutoIncrement;
use Sentience\DataLayer\Models\Attributes\Columns\Column;
use Sentience\DataLayer\Models\Attributes\Table\PrimaryKeys;
use Sentience\DataLayer\Models\Attributes\Table\Table;
use Sentience\DataLayer\Models\Model;

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
}
