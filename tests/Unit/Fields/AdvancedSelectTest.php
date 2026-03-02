<?php

use Aura\Base\Fields\AdvancedSelect;

describe('AdvancedSelect Field', function () {
    beforeEach(function () {
        $this->field = new AdvancedSelect;
    });

    describe('field configuration', function () {
        test('has correct view paths', function () {
            expect($this->field->edit)->toBe('aura::fields.advanced-select')
                ->and($this->field->view)->toBe('aura::fields.advanced-select-view')
                ->and($this->field->index)->toBe('aura::fields.advanced-select-index')
                ->and($this->field->filter)->toBe('aura::fields.filters.advanced-select');
        });

        test('belongs to JS Fields option group', function () {
            expect($this->field->optionGroup)->toBe('JS Fields');
        });

        test('getFields includes parent fields and custom fields', function () {
            $fields = $this->field->getFields();

            $slugs = collect($fields)->pluck('slug')->toArray();

            expect($slugs)->toContain('resource')
                ->and($slugs)->toContain('return_type')
                ->and($slugs)->toContain('thumbnail')
                ->and($slugs)->toContain('view_selected')
                ->and($slugs)->toContain('view_select')
                ->and($slugs)->toContain('view_view')
                ->and($slugs)->toContain('view_index')
                ->and($slugs)->toContain('polymorphic_relation')
                ->and($slugs)->toContain('create')
                ->and($slugs)->toContain('multiple');
        });

        test('return_type field has correct options', function () {
            $fields = $this->field->getFields();
            $returnTypeField = collect($fields)->firstWhere('slug', 'return_type');

            expect($returnTypeField['options'])->toBe([
                'id' => 'Ids',
                'object' => 'Objects',
            ])
                ->and($returnTypeField['default'])->toBe('id');
        });
    });

    describe('filterOptions', function () {
        test('returns contains filter option', function () {
            $options = $this->field->filterOptions();

            expect($options)->toBe([
                'contains' => __('contains'),
            ]);
        });
    });

    describe('isRelation', function () {
        test('returns true by default', function () {
            expect($this->field->isRelation())->toBeTrue();
        });

        test('returns true when polymorphic_relation is not set', function () {
            $field = ['slug' => 'test'];

            expect($this->field->isRelation($field))->toBeTrue();
        });

        test('returns true when polymorphic_relation is true', function () {
            $field = ['slug' => 'test', 'polymorphic_relation' => true];

            expect($this->field->isRelation($field))->toBeTrue();
        });

        test('returns false when polymorphic_relation is explicitly false', function () {
            $field = ['slug' => 'test', 'polymorphic_relation' => false];

            expect($this->field->isRelation($field))->toBeFalse();
        });
    });

    describe('get method', function () {
        describe('with polymorphic_relation false', function () {
            test('returns null for empty value', function () {
                $field = ['polymorphic_relation' => false];

                $result = $this->field->get(null, '', $field);

                expect($result)->toBeNull();
            });

            test('returns decoded JSON for string value', function () {
                $field = ['polymorphic_relation' => false];

                $result = $this->field->get(null, '[1,2,3]', $field);

                expect($result)->toBe([1, 2, 3]);
            });

            test('returns string as-is if not valid JSON', function () {
                $field = ['polymorphic_relation' => false];

                $result = $this->field->get(null, 'simple-string', $field);

                expect($result)->toBe('simple-string');
            });

            test('returns value directly for non-string values', function () {
                $field = ['polymorphic_relation' => false];

                $result = $this->field->get(null, [1, 2, 3], $field);

                expect($result)->toBe([1, 2, 3]);
            });
        });

        describe('with single select (multiple false)', function () {
            test('wraps integer value in array', function () {
                $field = ['multiple' => false];

                $result = $this->field->get(null, 5, $field);

                expect($result)->toBe([5]);
            });

            test('wraps numeric string in array', function () {
                $field = ['multiple' => false];

                $result = $this->field->get(null, '5', $field);

                expect($result)->toBe([5]);
            });

            test('returns empty array for non-numeric value', function () {
                $field = ['multiple' => false];

                $result = $this->field->get(null, 'invalid', $field);

                expect($result)->toBe([]);
            });

            test('returns empty array for empty collection', function () {
                $field = ['multiple' => false];

                $result = $this->field->get(null, collect([]), $field);

                expect($result)->toBe([]);
            });
        });

        describe('with multiple select (default)', function () {
            test('extracts ids from array of objects', function () {
                $value = [
                    ['id' => 1, 'title' => 'First'],
                    ['id' => 2, 'title' => 'Second'],
                ];

                $result = $this->field->get(null, $value, []);

                expect($result)->toBe([1, 2]);
            });

            test('returns integer value directly', function () {
                $result = $this->field->get(null, 5, []);

                expect($result)->toBe(5);
            });

            test('returns empty array for null value', function () {
                $result = $this->field->get(null, null, []);

                expect($result)->toBe([]);
            });
        });
    });

    describe('set method', function () {
        test('encodes value as JSON for multiple select', function () {
            $result = $this->field->set(null, ['multiple' => true], [1, 2, 3]);

            expect($result)->toBe('[1,2,3]');
        });

        test('encodes value as JSON for single select', function () {
            $result = $this->field->set(null, ['multiple' => false], 1);

            expect($result)->toBe('1');
        });

        test('encodes null value as JSON', function () {
            $result = $this->field->set(null, [], null);

            expect($result)->toBe('null');
        });

        test('treats missing multiple key as true', function () {
            $result = $this->field->set(null, [], [1, 2, 3]);

            expect($result)->toBe('[1,2,3]');
        });
    });

    describe('filter', function () {
        test('returns filter view path', function () {
            expect($this->field->filter())->toBe('aura::fields.filters.advanced-select');
        });
    });
});
