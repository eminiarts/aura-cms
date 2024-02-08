<?php

use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\InputFieldsHelpers;
use Eminiarts\Aura\Traits\InputFieldsTable;
use Eminiarts\Aura\Traits\InputFieldsValidation;
use Eminiarts\Aura\Traits\InteractsWithTable;

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
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required|string|max:255',
            ],
            [
                'slug' => 'body',
                'name' => 'Body',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => 'required|string',
            ],
        ];
    }
}
class TestInputFieldsClass2 extends Resource
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
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => ['required', 'string', 'max:255'],
            ],
            [
                'slug' => 'body',
                'name' => 'Body',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => ['required', 'string'],
            ],
        ];
    }
}

test('can get field by slug', function () {
    $inputFields = new TestInputFieldsClass();
    $field = $inputFields->fieldBySlug('title');

    $this->assertEquals([
        'slug' => 'title',
        'name' => 'Title',
        'type' => 'Eminiarts\\Aura\\Fields\\Text',
        'validation' => 'required|string|max:255',
    ], $field);
});

test('field validation rules are generated', function () {
    $inputFields = new TestInputFieldsClass();
    $validationRules = $inputFields->validationRules();

    $this->assertEquals([
        'title' => 'required|string|max:255',
        'body' => 'required|string',
    ], $validationRules);
});

test('can get table headers', function () {
    $inputFields = new TestInputFieldsClass();
    $tableHeaders = $inputFields->getTableHeaders();

    $this->assertInstanceOf(\Illuminate\Support\Collection::class, $tableHeaders);
    $this->assertCount(2, $tableHeaders);
    $this->assertEquals('Title', $tableHeaders->get('title'));
    $this->assertEquals('Body', $tableHeaders->get('body'));
});

test('check default table view', function () {
    $inputFields = new TestInputFieldsClass();
    $defaultTableView = $inputFields->defaultTableView();

    $this->assertEquals('list', $defaultTableView);
});

test('check default per page value', function () {
    $inputFields = new TestInputFieldsClass();
    $defaultPerPage = $inputFields->defaultPerPage();

    $this->assertEquals(10, $defaultPerPage);
});

test('field postFieldValidationRules prepend resource.fields', function () {
    $inputFields = new TestInputFieldsClass();

    $rules = $inputFields->postFieldValidationRules();

    expect($rules)->toBe([
        'resource.fields.title' => 'required|string|max:255',
        'resource.fields.body' => 'required|string',
    ]);
});

test('field validation allows array of rules', function () {
    $inputFields = new TestInputFieldsClass2();

    // Override the getFields method to return an array of rules
    $rules = $inputFields->postFieldValidationRules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveCount(2);
    expect($rules)->toHaveKey('resource.fields.title');
    expect($rules)->toHaveKey('resource.fields.body');

    // first rule should be ['required', 'string', 'max:255']
    expect($rules['resource.fields.title'])->toBeArray();
    expect($rules['resource.fields.title'])->toHaveCount(3);
    expect($rules['resource.fields.title'])->toContain('required');
    expect($rules['resource.fields.title'])->toContain('string');
    expect($rules['resource.fields.title'])->toContain('max:255');
});
