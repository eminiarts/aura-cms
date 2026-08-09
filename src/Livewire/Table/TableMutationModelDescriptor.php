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
        $this->assertModelMatches($this->model);
    }

    public function assertMatches(Builder $scope): void
    {
        $model = $scope->getModel();
        $query = $scope->getQuery();

        if (
            ! $this->modelMatches($model)
            || $query->getConnection() !== $this->connectionInstance
            || $query->from !== $this->table
        ) {
            abort(422, 'The table mutation scope does not match the mounted resource.');
        }
    }

    public function assertModelMatches(Model $model): void
    {
        if (! $this->modelMatches($model)) {
            abort(422, 'The table mutation model does not match the mounted resource.');
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

    public function connectionInstance(): ConnectionInterface
    {
        return $this->connectionInstance;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function fromState(array $state): self
    {
        if (
            array_keys($state) !== [
                'class',
                'connection',
                'incrementing',
                'key_name',
                'key_type',
                'morph_class',
                'table',
            ]
            || ! is_string($state['class'])
            || ! is_a($state['class'], Model::class, true)
            || ! is_a($state['class'], TableResource::class, true)
            || ! is_string($state['connection'])
            || ! is_bool($state['incrementing'])
            || ! is_string($state['key_name'])
            || ! is_string($state['key_type'])
            || ! is_string($state['morph_class'])
            || ! is_string($state['table'])
        ) {
            abort(422, 'The stored table mutation model is invalid.');
        }

        $model = new $state['class'];
        $model->setConnection($state['connection']);
        $model->setTable($state['table']);
        $model->setKeyName($state['key_name']);
        $model->setKeyType($state['key_type']);
        $model->setIncrementing($state['incrementing']);
        $descriptor = new self($model);

        if ($descriptor->state() !== $state) {
            abort(422, 'The stored table mutation model no longer matches the resource.');
        }

        return $descriptor;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function hydrate(array $attributes): Model&TableResource
    {
        $model = $this->model();
        $model->setRawAttributes($attributes, true);
        $model->setRelations([]);
        $model->exists = true;
        $model->wasRecentlyCreated = false;
        $model->syncChanges();

        $this->assertModelMatches($model);

        return $model;
    }

    public function model(): Model&TableResource
    {
        $model = clone $this->model;
        $this->configure($model);
        $this->assertModelMatches($model);

        return $model;
    }

    /**
     * @return array{
     *     class: class-string<Model>,
     *     connection: string,
     *     incrementing: bool,
     *     key_name: string,
     *     key_type: string,
     *     morph_class: string,
     *     table: string
     * }
     */
    public function state(): array
    {
        return [
            'class' => $this->class,
            'connection' => $this->connection,
            'incrementing' => $this->incrementing,
            'key_name' => $this->keyName,
            'key_type' => $this->keyType,
            'morph_class' => $this->morphClass,
            'table' => $this->table,
        ];
    }

    private function modelMatches(Model $model): bool
    {
        return $model instanceof TableResource
            && $model::class === $this->class
            && $model->getTable() === $this->table
            && $model->getConnection()->getName() === $this->connection
            && $model->getConnection() === $this->connectionInstance
            && $model->getKeyName() === $this->keyName
            && $model->getKeyType() === $this->keyType
            && $model->getIncrementing() === $this->incrementing
            && $model->getMorphClass() === $this->morphClass;
    }
}
