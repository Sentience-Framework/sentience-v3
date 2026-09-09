<?php

namespace Sentience\ORM\Database\Queries;

use DateTimeInterface;
use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\ORM\Database\Queries\Objects\ConditionGroup;
use Sentience\ORM\Models\Reflection\ReflectionModel;

class UpdateModelsQuery extends ModelsQueryAbstract
{
    use WhereTrait;

    protected array $updates = [];

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, array $models)
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

            $updateQuery = $this->database->update($table);

            $values = [];

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isColumn()) {
                    continue;
                }

                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();
                $value = $model->{$property};

                if ($reflectionModelProperty->isPrimaryKey()) {
                    $updateQuery->whereEquals($column, $value);

                    continue;
                }

                $values[$column] = $this->encodeValue($reflectionModelProperty, $value);
            }

            $updateQuery->set([...$values, ...$this->updates]);
            $updateQuery->whereGroup(fn (): ConditionGroup => (new ConditionGroup(ChainEnum::And, false))->addConditions($this->where));

            $updateQuery->execute($emulatePrepare);
        }

        return $this->models;
    }

    public function updateColumns(array $values): static
    {
        $this->updates = array_merge($this->updates, $values);

        return $this;
    }

    public function updateColumn(string $column, null|bool|int|float|string|DateTimeInterface $value): static
    {
        $this->updates[$column] = $value;

        return $this;
    }
}
