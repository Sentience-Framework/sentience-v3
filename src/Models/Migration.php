<?php

declare(strict_types=1);

namespace src\Models;

use DateTime;
use sentience\Models\Attributes\AutoIncrement;
use sentience\Models\Attributes\Column;
use sentience\Models\Attributes\PrimaryKeys;
use sentience\Models\Attributes\Table;
use sentience\Models\Attributes\UniqueConstraint;
use sentience\Models\Model;

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

    #[Column(column: 'filename')]
    public string $filename;

    #[Column('applied_at')]
    public DateTime $appliedAt;
}
