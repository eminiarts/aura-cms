<?php

use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\InputFieldsTable;
use Eminiarts\Aura\Traits\InputFieldsHelpers;
use Eminiarts\Aura\Traits\InteractsWithTable;
use Eminiarts\Aura\Traits\InputFieldsValidation;

class TestInputFieldsClass extends Resource
{
    use InputFields;
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;
    use InteractsWithTable;

    public function getFields()
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

    public function getFields()
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
    $this->assertCount(3, $tableHeaders);
    $this->assertEquals('ID', $tableHeaders->get('id'));
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

test('field postFieldValidationRules prepend post.fields', function () {
    $inputFields = new TestInputFieldsClass();

    $rules = $inputFields->postFieldValidationRules();

    expect($rules)->toBe([
        'post.fields.title' => 'required|string|max:255',
        'post.fields.body' => 'required|string',
    ]);
});

test('field validation allows array of rules', function () {
    $inputFields = new TestInputFieldsClass2();

    // Override the getFields method to return an array of rules
    $rules = $inputFields->postFieldValidationRules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveCount(2);
    expect($rules)->toHaveKey('post.fields.title');
    expect($rules)->toHaveKey('post.fields.body');

    // first rule should be ['required', 'string', 'max:255']
    expect($rules['post.fields.title'])->toBeArray();
    expect($rules['post.fields.title'])->toHaveCount(3);
    expect($rules['post.fields.title'])->toContain('required');
    expect($rules['post.fields.title'])->toContain('string');
    expect($rules['post.fields.title'])->toContain('max:255');
});
