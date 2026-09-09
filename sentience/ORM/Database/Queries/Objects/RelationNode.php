<?php

namespace Sentience\ORM\Database\Queries\Objects;

use Closure;
use Sentience\Exceptions\RelationException;
use Sentience\Helpers\Arrays;
use Sentience\ORM\Models\Attributes\Relations\Relation;
use Sentience\ORM\Models\Reflection\ReflectionModel;

class RelationNode
{
    public const string SEPARATOR = '->';

    protected array $children = [];

    public function __construct(
        public string $path,
        public string $property,
        public ?Relation $relation,
        public ReflectionModel $reflectionModel,
        public ?Closure $callback = null
    ) {
    }

    public static function tree(ReflectionModel $reflectionModel, array $relations): static
    {
        $root = new static('', '', null, $reflectionModel);

        foreach ($relations as $relation => $callback) {
            $root->add(explode(static::SEPARATOR, $relation), $callback);
        }

        return $root;
    }

    public function add(array $properties, ?Closure $callback = null): void
    {
        $property = array_shift($properties);

        if (!$property) {
            return;
        }

        $child = $this->children[$property] ?? $this->addChild($property);

        if ($callback && Arrays::empty($properties)) {
            $child->callback = $callback;
        }

        $child->add($properties, $callback);
    }

    protected function addChild(string $property): static
    {
        $relation = $this->reflectionModel->hasProperty($property)
            ? $this->reflectionModel->getRelation($property)
            : null;

        if (!$relation) {
            throw new RelationException('%s does not have relation %s', $this->reflectionModel->getClass(), $property);
        }

        $path = $this->path == ''
            ? $property
            : sprintf('%s%s%s', $this->path, static::SEPARATOR, $property);

        $child = new static($path, $property, $relation, new ReflectionModel($relation->model));

        $this->children[$property] = $child;

        return $child;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getPaths(): array
    {
        $paths = [];

        foreach ($this->children as $child) {
            $paths[$child->property] = $child->callback;

            foreach ($child->getPaths() as $path => $callback) {
                $paths[sprintf('%s%s%s', $child->property, static::SEPARATOR, $path)] = $callback;
            }
        }

        return $paths;
    }

    public function isToMany(): bool
    {
        return $this->relation ? $this->relation::TO_MANY : false;
    }
}
