<?php

namespace Aura\Base\Livewire\Table;

use Aura\Base\Contracts\TableResource;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class TableMutationModelDescriptor
{
    /** @var class-string<Model> */
    public readonly string $class;

    public readonly string $connection;

    public readonly bool $incrementing;

    public readonly string $keyName;

    public readonly string $keyType;

    public readonly string $morphClass;

    public readonly string $table;

    private ConnectionInterface $connectionInstance;

    private Model&TableResource $model;

    public function __construct(Model&TableResource $model)
    {
        $this->model = clone $model;
        $this->class = $model::class;
        $this->connection = $model->getConnection()->getName();
        $this->connectionInstance = $model->getConnection();
        $this->incrementing = $model->getIncrementing();
        $this->keyName = $model->getKeyName();
        $this->keyType = $model->getKeyType();
        $this->morphClass = $model->getMorphClass();
        $this->table = $model->getTable();

        $this->configure($this->model);
    }

    public function assertMatches(Builder $scope): void
    {
        $model = $scope->getModel();
        $query = $scope->getQuery();

        if (
            $model::class !== $this->class
            || $model->getTable() !== $this->table
            || $model->getConnection()->getName() !== $this->connection
            || $model->getKeyName() !== $this->keyName
            || $model->getKeyType() !== $this->keyType
            || $model->getIncrementing() !== $this->incrementing
            || $model->getMorphClass() !== $this->morphClass
            || $query->getConnection() !== $this->connectionInstance
            || $query->from !== $this->table
        ) {
            abort(422, 'The table mutation scope does not match the mounted resource.');
        }
    }

    public function canonicalIdentity(mixed $key): string
    {
        if ((! is_int($key) && ! is_string($key)) || (is_string($key) && $key === '')) {
            abort(422, 'The resolved table record identity is invalid.');
        }

        return json_encode([
            'class' => $this->class,
            'connection' => $this->connection,
            'morph' => $this->morphClass,
            'key' => (string) $key,
        ], JSON_THROW_ON_ERROR);
    }

    public function configure(Model $model): void
    {
        if ($model::class !== $this->class || ! $model instanceof TableResource) {
            abort(422, 'The table mutation model does not match the mounted resource.');
        }

        $model->setConnection($this->connection);
        $model->setTable($this->table);
        $model->setKeyName($this->keyName);
        $model->setKeyType($this->keyType);
        $model->setIncrementing($this->incrementing);
    }

    public function model(): Model&TableResource
    {
        $model = clone $this->model;
        $this->configure($model);

        return $model;
    }
}
