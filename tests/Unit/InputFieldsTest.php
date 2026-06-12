<?php

use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InputFieldsHelpers;
use Aura\Base\Traits\InputFieldsTable;
use Aura\Base\Traits\InputFieldsValidation;
use Aura\Base\Traits\InteractsWithTable;
use Illuminate\Support\Collection;

class TestInputFieldsClass extends Resource
{
    use InputFields;
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;
    use InteractsWithTable;

    public static function getFields()
    {
        return [
            [
                'slug' => 'title',
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|string|max:255',
            ],
            [
                'slug' => 'body',
                'name' => 'Body',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => 'required|string',
            ],
            [
                'slug' => 'hidden_field',
                'name' => 'Hidden Field',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable',
                'on_index' => false,
            ],
        ];
    }
}

class TestInputFieldsWithArrayRules extends Resource
{
    use InputFields;
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;
    use InteractsWithTable;

    public static function getFields()
    {
        return [
            [
                'slug' => 'title',
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => ['required', 'string', 'max:255'],
            ],
            [
                'slug' => 'body',
                'name' => 'Body',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => ['required', 'string'],
            ],
        ];
    }
}

// InputFieldsHelpers tests
describe('InputFieldsHelpers', function () {
    test('fieldBySlug returns field configuration by slug', function () {
        $resource = new TestInputFieldsClass;

        $field = $resource->fieldBySlug('title');

        expect($field)->toBe([
            'slug' => 'title',
            'name' => 'Title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|string|max:255',
        ]);
    });

    test('fieldBySlug returns null for non-existent slug', function () {
        $resource = new TestInputFieldsClass;

        $field = $resource->fieldBySlug('non_existent');

        expect($field)->toBeNull();
    });

    test('fieldBySlug caches results for performance', function () {
        $resource = new TestInputFieldsClass;

        // First call populates cache
        $field1 = $resource->fieldBySlug('title');
        $field2 = $resource->fieldBySlug('title');

        expect($field1)->toBe($field2);
    });

    test('fieldClassBySlug returns field class instance', function () {
        $resource = new TestInputFieldsClass;

        $fieldClass = $resource->fieldClassBySlug('title');

        expect($fieldClass)->toBeInstanceOf(Text::class);
    });

    test('fieldClassBySlug returns false for non-existent field', function () {
        $resource = new TestInputFieldsClass;

        $fieldClass = $resource->fieldClassBySlug('non_existent');

        expect($fieldClass)->toBeFalse();
    });

    test('fieldsCollection returns collection of fields', function () {
        $resource = new TestInputFieldsClass;

        $fields = $resource->fieldsCollection();

        expect($fields)->toBeInstanceOf(Collection::class)
            ->and($fields)->toHaveCount(3);
    });

    test('getFieldSlugs returns all field slugs', function () {
        $resource = new TestInputFieldsClass;

        $slugs = $resource->getFieldSlugs();

        expect($slugs->toArray())->toBe(['title', 'body', 'hidden_field']);
    });

    test('mappedFields returns fields with field instances', function () {
        $resource = new TestInputFieldsClass;

        $mapped = $resource->mappedFields();

        expect($mapped)->toBeInstanceOf(Collection::class)
            ->and($mapped->first())->toHaveKey('field')
            ->and($mapped->first())->toHaveKey('field_type');
    });

    test('mappedFieldBySlug returns mapped field by slug', function () {
        $resource = new TestInputFieldsClass;

        $mapped = $resource->mappedFieldBySlug('title');

        expect($mapped)->toHaveKey('field')
            ->and($mapped)->toHaveKey('field_type')
            ->and($mapped['slug'])->toBe('title');
    });
});

// InputFieldsValidation tests
describe('InputFieldsValidation', function () {
    test('validationRules generates rules from string validation', function () {
        $resource = new TestInputFieldsClass;

        $rules = $resource->validationRules();

        expect($rules)->toHaveKey('title')
            ->and($rules['title'])->toBe('required|string|max:255')
            ->and($rules)->toHaveKey('body')
            ->and($rules['body'])->toBe('required|string');
    });

    test('validationRules handles array validation rules', function () {
        $resource = new TestInputFieldsWithArrayRules;

        $rules = $resource->validationRules();

        expect($rules)->toHaveKey('title')
            ->and($rules['title'])->toBeArray()
            ->and($rules['title'])->toContain('required')
            ->and($rules['title'])->toContain('string')
            ->and($rules['title'])->toContain('max:255');
    });

    test('resourceFieldValidationRules prepends form.fields prefix', function () {
        $resource = new TestInputFieldsClass;

        $rules = $resource->resourceFieldValidationRules();

        expect($rules)->toHaveKey('form.fields.title')
            ->and($rules['form.fields.title'])->toBe('required|string|max:255')
            ->and($rules)->toHaveKey('form.fields.body')
            ->and($rules['form.fields.body'])->toBe('required|string');
    });

    test('resourceFieldValidationRules handles array rules correctly', function () {
        $resource = new TestInputFieldsWithArrayRules;

        $rules = $resource->resourceFieldValidationRules();

        expect($rules)->toHaveKey('form.fields.title')
            ->and($rules['form.fields.title'])->toBeArray()
            ->and($rules['form.fields.title'])->toHaveCount(3)
            ->and($rules['form.fields.title'])->toContain('required')
            ->and($rules['form.fields.title'])->toContain('string')
            ->and($rules['form.fields.title'])->toContain('max:255');
    });
});

// InputFieldsTable tests
describe('InputFieldsTable', function () {
    test('getTableHeaders returns collection of header names', function () {
        $resource = new TestInputFieldsClass;

        $headers = $resource->getTableHeaders();

        expect($headers)->toBeInstanceOf(Collection::class)
            ->and($headers->get('title'))->toBe('Title')
            ->and($headers->get('body'))->toBe('Body');
    });

    test('getTableHeaders excludes fields with on_index false', function () {
        $resource = new TestInputFieldsClass;

        $headers = $resource->getTableHeaders();

        expect($headers->has('hidden_field'))->toBeFalse();
    });

    test('getColumns returns headers as array', function () {
        $resource = new TestInputFieldsClass;

        $columns = $resource->getColumns();

        expect($columns)->toBeArray()
            ->and($columns)->toHaveKey('title')
            ->and($columns['title'])->toBe('Title');
    });

    test('getDefaultColumns returns all columns set to true', function () {
        $resource = new TestInputFieldsClass;

        $defaults = $resource->getDefaultColumns();

        expect($defaults)->toBeArray()
            ->and($defaults['title'])->toBeTrue()
            ->and($defaults['body'])->toBeTrue();
    });

    test('isFieldOnIndex returns true by default', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->isFieldOnIndex('title'))->toBeTrue();
    });

    test('isFieldOnIndex returns false for hidden fields', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->isFieldOnIndex('hidden_field'))->toBeFalse();
    });
});

// InteractsWithTable tests
describe('InteractsWithTable', function () {
    test('defaultTableView returns list', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->defaultTableView())->toBe('list');
    });

    test('defaultPerPage returns 10', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->defaultPerPage())->toBe(10);
    });

    test('defaultTableSort returns id', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->defaultTableSort())->toBe('id');
    });

    test('defaultTableSortDirection returns desc', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->defaultTableSortDirection())->toBe('desc');
    });

    test('showTableSettings returns true by default', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->showTableSettings())->toBeTrue();
    });

    test('tableGridView returns false by default', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->tableGridView())->toBeFalse();
    });

    test('tableKanbanView returns false by default', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->tableKanbanView())->toBeFalse();
    });

    test('tableView returns default view path', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->tableView())->toBe('aura::components.table.list-view');
    });

    test('kanbanQuery returns false by default', function () {
        $resource = new TestInputFieldsClass;

        expect($resource->kanbanQuery(null))->toBeFalse();
    });
});
