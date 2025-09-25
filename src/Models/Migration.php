<?php

namespace Src\Models;

use DateTime;
use Sentience\DataLayer\Models\Attributes\Columns\AutoIncrement;
use Sentience\DataLayer\Models\Attributes\Columns\Column;
use Sentience\DataLayer\Models\Attributes\Table\PrimaryKeys;
use Sentience\DataLayer\Models\Attributes\Table\Table;
use Sentience\DataLayer\Models\Attributes\Table\UniqueConstraint;
use Sentience\DataLayer\Models\Model;

#[Table('migrations')]
#[PrimaryKeys(['id'])]
#[UniqueConstraint(['filename'])]
class Migration extends Model
{
    #[Column('id')]
    #[AutoIncrement]
    public int $id;

    #[Column('batch')]
    public int $batch;

    #[Column('filename')]
    public string $filename;

    #[Column('applied_at')]
    public DateTime $appliedAt;
}
