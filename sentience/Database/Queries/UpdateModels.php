<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use DateTimeInterface;
use Sentience\Database\Queries\Traits\Where;
use Sentience\Helpers\Reflector;
use Sentience\Models\Attributes\Columns\AutoIncrement;

class UpdateModels extends ModelsQueryAbstract
{
    use Where;

    protected array $updates = [];

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $query = $this->database->update($model::getTable());

            $columns = $model::getColumns();

            $values = [];

            foreach ($columns as $column => $property) {
                if (!Reflector::isPropertyInitialized($model, $property)) {
                    continue;
                }

                $values[$column] = $model->{$property};
            }

            $query->values([
                ...$values,
                ...$this->updates
            ]);

            $primaryKeys = $model::getPrimaryKeys();

            foreach ($primaryKeys as $column => $property) {
                $query->whereEquals($column, $model->{$property});
            }

            $query->returning();

            $queryWithParams = $query->toQueryWithParams();

            $results = $this->database->prepared($queryWithParams->query, $queryWithParams->params);

            $updatedRow = $results->fetchAssoc();

            if ($updatedRow) {
                $model->fromDatabase($updatedRow);
            }

            $lastInsertId = $results->lastInsertId();

            if (!$lastInsertId) {
                continue;
            }

            $primaryKeys = $model::getPrimaryKeys();

            foreach ($primaryKeys as $property) {
                if (!Reflector::propertyHasAttribute($model, $property, AutoIncrement::class)) {
                    continue;
                }

                $model->{$property} = $lastInsertId;
            }
        }

        return $this->models;
    }

    public function updateColumns(array $columns): static
    {
        array_merge($this->updates, $columns);

        return $this;
    }

    public function updateColumn(string $column, null|bool|int|float|string|DateTimeInterface $value): static
    {
        $this->updates[$column] = $value;

        return $this;
    }
}
