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
            $primaryKeys = $reflectionModel->getPrimaryKeys();

            $insertQuery = $this->database->insert($table);

            $values = [];
            $autoIncrementPrimaryKeyColumn = null;

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                $column = $reflectionModelProperty->getColumn();

                if ($reflectionModelProperty->isPrimaryKey() && $reflectionModelProperty->isAutoIncrement()) {
                    $autoIncrementPrimaryKeyColumn = $column;
                }

                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();

                $values[$column] = $this->getValueIfBackedEnum($model->{$property});
            }

            $insertQuery->values($values);

            if (!is_null($this->onDuplicateUpdate)) {
                $uniqueConstraint = $reflectionModel->getUniqueConstraint();

                $uniqueColumns = $uniqueConstraint
                    ? $uniqueConstraint->columns
                    : $primaryKeys;

                $updateValues = array_filter(
                    $values,
                    fn(string $column): bool => !in_array(
                        $column,
                        $this->onDuplicateUpdateExcludeColumns
                    ),
                    ARRAY_FILTER_USE_KEY
                );

                $this->onDuplicateUpdate
                    ? $insertQuery->onConflictUpdate(
                        $uniqueColumns,
                        $updateValues,
                        $autoIncrementPrimaryKeyColumn
                    )
                    : $insertQuery->onConflictIgnore(
                        $uniqueColumns,
                        $autoIncrementPrimaryKeyColumn
                    );
            }

            $insertQuery->returning($primaryKeys);

            $result = $insertQuery->execute($emulatePrepare);

            $insertedRow = $result->fetchAssoc();

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

                $property = $reflectionModelProperty->getProperty();

                $model->{$property} = (int) $lastInsertId;

                break;
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
