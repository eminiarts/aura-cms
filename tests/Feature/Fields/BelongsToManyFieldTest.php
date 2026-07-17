<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\BelongsToMany;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('BelongsToMany Field Configuration', function () {
    test('has correct properties', function () {
        $field = new BelongsToMany;

        expect($field->optionGroup)->toBe('Relationship Fields')
            ->and($field->edit)->toBe('aura::fields.has-many')
            ->and($field->type)->toBe('relation')
            ->and($field->group)->toBeTrue();
    });

    test('is treated as a relation field', function () {
        expect((new BelongsToMany)->isRelation())->toBeTrue();
    });
});

describe('BelongsToMany Field Query Scoping', function () {
    test('queryFor scopes to the current user id for a non-User/Team model', function () {
        $field = new BelongsToMany;

        // Build a lightweight component-like object exposing model + field.
        $model = createPost(); // a Post resource (not User/Team)
        $component = new class($model, [])
        {
            public $field;

            public $model;

            public function __construct($model, $field)
            {
                $this->model = $model;
                $this->field = $field;
            }
        };

        $query = User::query();
        $scoped = $field->queryFor($query, $component);

        // Assert the where clause on user_id was applied.
        $wheres = collect($scoped->getQuery()->wheres);
        expect($wheres->contains(fn ($w) => ($w['column'] ?? null) === 'user_id'))->toBeTrue();
    });
});
