<?php

namespace Sentience\ORM\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\ORM\Models\Reflection\ReflectionModel;

class InsertModelsQuery extends ModelsQueryAbstract
{
    protected ?bool $onDuplicateUpdate = null;
    protected array $onDuplicateUpdateExcludeColumns = [];
    protected bool $emulateUpsert = false;
    protected bool $emulateUpsertInTransaction = false;

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

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                $column = $reflectionModelProperty->getColumn();

                if ($reflectionModelProperty->isPrimaryKey() && $reflectionModelProperty->isAutoIncrement()) {
                    $this->emulateUpsert
                        ? $insertQuery->emulateOnConflict($column, $this->emulateUpsertInTransaction)
                        : $insertQuery->lastInsertId($column);
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
                    fn (string $column): bool => !in_array(
                        $column,
                        $this->onDuplicateUpdateExcludeColumns
                    ),
                    ARRAY_FILTER_USE_KEY
                );

                $this->onDuplicateUpdate
                    ? $insertQuery->onConflictUpdate(
                        $uniqueColumns,
                        $updateValues
                    )
                    : $insertQuery->onConflictIgnore(
                        $uniqueColumns
                    );
            }

            $insertQuery->returning($reflectionModel->getColumns());

            $result = $insertQuery->execute($emulatePrepare);

            $insertedRow = $result->fetchAssoc();

            if ($insertedRow) {
                $this->mapAssocToModel($model, $insertedRow);

                continue;
            }

            $lastInsertId = $this->database->lastInsertId();

            if ($lastInsertId) {
                foreach ($reflectionModelProperties as $reflectionModelProperty) {
                    if (!$reflectionModelProperty->isAutoIncrement()) {
                        continue;
                    }

                    $property = $reflectionModelProperty->getProperty();

                    $model->{$property} = $lastInsertId;

                    break;
                }
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

    public function emulateUpsert(bool $inTransaction = false): static
    {
        $this->emulateUpsert = true;
        $this->emulateUpsertInTransaction = $inTransaction;

        return $this;
    }
}
