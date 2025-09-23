<?php

namespace Src\Models;

use Sentience\ORM\Models\Attributes\Columns\AutoIncrement;
use Sentience\ORM\Models\Attributes\Columns\Column;
use Sentience\ORM\Models\Attributes\Table\PrimaryKeys;
use Sentience\ORM\Models\Attributes\Table\Table;
use Sentience\ORM\Models\Attributes\Table\UniqueConstraint;
use Sentience\ORM\Models\Model;
use Sentience\Timestamp\Timestamp;

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
    public Timestamp $appliedAt;
}
