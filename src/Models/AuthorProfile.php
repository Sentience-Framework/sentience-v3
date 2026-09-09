<?php

namespace Src\Models;

use Sentience\ORM\Models\Attributes\Columns\AutoIncrement;
use Sentience\ORM\Models\Attributes\Columns\Cast;
use Sentience\ORM\Models\Attributes\Columns\Column;
use Sentience\ORM\Models\Attributes\Columns\Json;
use Sentience\ORM\Models\Attributes\Relations\BelongsTo;
use Sentience\ORM\Models\Attributes\Table\PrimaryKeys;
use Sentience\ORM\Models\Attributes\Table\Table;
use Sentience\ORM\Models\Model;

#[Table('author_profiles')]
#[PrimaryKeys(['id'])]
class AuthorProfile extends Model
{
    #[Column('id')]
    #[AutoIncrement]
    public int $id;

    #[Column('author_id')]
    public int $authorId;

    #[Column('biography')]
    public string $biography;

    #[Column('links')]
    #[Json]
    public array $links;

    #[Column('preferences')]
    #[Json]
    public object $preferences;

    #[Column('tags')]
    #[Cast('serialize', 'unserialize')]
    public array $tags;

    #[BelongsTo(Author::class, 'authorId<-id')]
    public ?Author $author;
}
