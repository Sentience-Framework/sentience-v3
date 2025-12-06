<?php

namespace Src\Models;

use Sentience\ORM\Models\Attributes\Columns\AutoIncrement;
use Sentience\ORM\Models\Attributes\Columns\Column;
use Sentience\ORM\Models\Attributes\Table\PrimaryKeys;
use Sentience\ORM\Models\Attributes\Table\Table;
use Sentience\ORM\Models\Model;

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
