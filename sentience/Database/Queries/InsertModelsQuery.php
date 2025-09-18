<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Models\Reflection\ReflectionModel;

class InsertModelsQuery extends ModelsQueryAbstract
{
    protected ?bool $onDuplicateUpdate = null;
    protected array $onDuplicateUpdateExcludeColumns = [];

    public function __construct(Database $database, DialectInterface $dialect, array $model)
    {
        parent::__construct($database, $dialect, $model);
    }

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $reflectionModel = new ReflectionModel($model);
            $reflectionModelProperties = $reflectionModel->getProperties();

            $reflectionModel = new ReflectionModel($model);

            $table = $reflectionModel->getTable();

            $insertQuery = $this->database->insert($table);

            $values = [];
            $autoIncrementPrimaryKeyColumn = null;

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();

                $values[$column] = $this->getValueIfBackedEnum($model->{$property});

                if ($reflectionModelProperty->isPrimaryKey() && $reflectionModelProperty->isAutoIncrement()) {
                    $autoIncrementPrimaryKeyColumn = $column;
                }
            }

            $insertQuery->values($values);

            if (!is_null($this->onDuplicateUpdate)) {
                $uniqueConstraint = $reflectionModel->getUniqueConstraint();

                $columns = $uniqueConstraint
                    ? $uniqueConstraint->columns
                    : $reflectionModel->getPrimaryKeys();

                $columns = array_filter(
                    $columns,
                    fn(string $column): bool => !in_array($column, $this->onDuplicateUpdateExcludeColumns)
                );

                $this->onDuplicateUpdate
                    ? $insertQuery->onConflictUpdate($columns, $values, $autoIncrementPrimaryKeyColumn)
                    : $insertQuery->onConflictIgnore($columns, $autoIncrementPrimaryKeyColumn);
            }

            $results = $insertQuery->execute();

            $insertedRow = $results->fetchAssoc();

            if ($insertedRow) {
                $this->mapAssocToModel($model, $insertedRow);
            }

            $lastInsertId = $this->database->lastInsertId();

            if (empty($lastInsertId)) {
                continue;
            }

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isAutoIncrement()) {
                    continue;
                }

                $model->{$reflectionModelProperty->getProperty()} = (int) $lastInsertId;
            }
        }

        return $this->models;
    }

    public function onDuplicateIgnore(): static
    {
        $this->onDuplicateUpdate = false;

        return $this;
    }

    public function onDuplicateUpdate(array $excludeColumns = []): static
    {
        $this->onDuplicateUpdate = true;
        $this->onDuplicateUpdateExcludeColumns = $excludeColumns;

        return $this;
    }
}
