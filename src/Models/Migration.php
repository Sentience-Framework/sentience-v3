<?php

declare(strict_types=1);

namespace Src\Models;

use DateTime;
use Sentience\Models\Attributes\AutoIncrement;
use Sentience\Models\Attributes\Column;
use Sentience\Models\Attributes\PrimaryKeys;
use Sentience\Models\Attributes\Table;
use Sentience\Models\Attributes\UniqueConstraint;
use Sentience\Models\Model;

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
