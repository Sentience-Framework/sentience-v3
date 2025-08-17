<?php

declare(strict_types=1);

namespace Src\Models;

use JsonSerializable;
use Modules\Models\Attributes\Columns\AutoIncrement;
use Modules\Models\Attributes\Columns\Column;
use Modules\Models\Attributes\Table\PrimaryKeys;
use Modules\Models\Attributes\Table\Table;
use Modules\Models\Attributes\Table\UniqueConstraint;
use Modules\Models\Model;
use Modules\Models\Traits\IsJsonSerializable;
use Modules\Timestamp\Timestamp;

#[Table('migrations')]
#[PrimaryKeys(['id'])]
#[UniqueConstraint(['filename'])]
class Migration extends Model implements JsonSerializable
{
    use IsJsonSerializable;

    #[Column('id')]
    #[AutoIncrement]
    public int $id;

    #[Column('batch')]
    public int $batch;

    #[Column(column: 'filename')]
    public string $filename;

    #[Column('applied_at')]
    public Timestamp $appliedAt;
}
