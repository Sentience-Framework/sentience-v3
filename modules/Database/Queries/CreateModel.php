<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use ReflectionClass;
use ReflectionProperty;
use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Traits\IfNotExists;
use Modules\Exceptions\ModelException;
use Modules\Helpers\Reflector;
use Modules\Models\Attributes\Columns\AutoIncrement;
use Modules\Models\Attributes\Table\UniqueConstraint;

class CreateModel extends ModelsQueryAbstract
{
    use IfNotExists;

    public function __construct(Database $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, $model);
    }

    public function execute(): null
    {
        $model = $this->models[0];

        $this->validateModel($model);

        $columns = $model::getColumns();
        $primaryKeys = $model::getPrimaryKeys();
        $uniqueColumns = $model::getUniqueColumns();

        $query = $this->database->createTable($model::getTable())
            ->primaryKeys(array_keys($primaryKeys));

        if ($this->ifNotExists) {
            $query->ifNotExists();
        }

        foreach ($columns as $column => $property) {
            $reflectionProperty = new ReflectionProperty($model, $property);

            if (!Reflector::hasNamedType($model, $property)) {
                throw new ModelException('empty or union types are not allowed as model properties');
            }

            $reflectionProperty = new ReflectionProperty($model, $property);
            $reflectionType = $reflectionProperty->getType();

            $propertyType = Reflector::toNamedType($reflectionType);
            $propertyAllowsNull = $reflectionType->allowsNull();
            $propertyHasDefaultValue = $reflectionProperty->hasDefaultValue();
            $propertyDefaultValue = $reflectionProperty->getDefaultValue();
            $propertyIsPrimaryKey = in_array($property, array_values($primaryKeys));
            $propertyHasAutoIncrementAttribute = Reflector::propertyHasAttribute($model, $property, AutoIncrement::class);

            $columnType = $this->dialect->phpTypeToColumnType(
                $propertyType,
                $propertyHasAutoIncrementAttribute,
                $propertyIsPrimaryKey,
                in_array($property, array_values($uniqueColumns))
            );

            $query->column(
                $column,
                $columnType,
                !$propertyAllowsNull,
                $propertyHasDefaultValue ? $propertyDefaultValue : null,
                $propertyHasAutoIncrementAttribute
            );
        }

        if (Reflector::classHasAttribute($model, UniqueConstraint::class)) {
            $uniqueConstraintAttributes = (new ReflectionClass($model))->getAttributes(UniqueConstraint::class);

            $uniqueConstraint = $uniqueConstraintAttributes[0]->newInstance();

            $query->uniqueConstraint(
                array_map(
                    fn (string $property): string => $model::getColumn($property),
                    $uniqueConstraint->properties
                ),
                $uniqueConstraint->name
            );
        }

        $query->execute();

        return null;
    }
}
