<?php

namespace Src\Models;

use Sentience\ORM\Models\Attributes\Columns\Column;
use Sentience\ORM\Models\Attributes\Columns\Json;
use Sentience\ORM\Models\Attributes\Table\PrimaryKeys;
use Sentience\ORM\Models\Attributes\Table\Table;
use Sentience\ORM\Models\Model;

#[Table('bad_casts')]
#[PrimaryKeys(['id'])]
class BadCastModel extends Model
{
    #[Column('id')]
    public int $id;

    #[Column('name')]
    #[Json]
    public string $name;
}
