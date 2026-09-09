<?php

namespace Src\Models;

use Sentience\ORM\Models\Attributes\Columns\AutoIncrement;
use Sentience\ORM\Models\Attributes\Columns\Column;
use Sentience\ORM\Models\Attributes\Table\PrimaryKeys;
use Sentience\ORM\Models\Attributes\Table\Table;
use Sentience\ORM\Models\Model;

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
