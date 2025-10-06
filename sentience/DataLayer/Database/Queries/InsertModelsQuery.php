<?php

namespace Sentience\DataLayer\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\DataLayer\Models\Reflection\ReflectionModel;

class InsertModelsQuery extends ModelsQueryAbstract
{
    protected ?bool $onDuplicateUpdate = null;
    protected array $onDuplicateUpdateExcludeColumns = [];

    public function __construct(Database $database, DialectInterface $dialect, array $models)
    {
        parent::__construct($database, $dialect, $models);
    }

    public function execute(bool $emulatePrepare = false): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $reflectionModel = new ReflectionModel($model);
            $reflectionModelProperties = $reflectionModel->getProperties();

            $table = $reflectionModel->getTable();
            $columns = $reflectionModel->getColumns();

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

                $uniqueColumns = $uniqueConstraint
                    ? $uniqueConstraint->columns
                    : $reflectionModel->getPrimaryKeys();

                $uniqueColumns = array_filter(
                    $uniqueColumns,
                    fn (string $column): bool => !in_array($column, $this->onDuplicateUpdateExcludeColumns)
                );

                $this->onDuplicateUpdate
                    ? $insertQuery->onConflictUpdate($uniqueColumns, $values, $autoIncrementPrimaryKeyColumn)
                    : $insertQuery->onConflictIgnore($uniqueColumns, $autoIncrementPrimaryKeyColumn);
            }

            $insertQuery->returning($columns);

            $result = $insertQuery->execute($emulatePrepare);

            $insertedRow = $result->fetchAssoc();

            if ($insertedRow) {
                $this->mapAssocToModel($model, $insertedRow);
            }

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
