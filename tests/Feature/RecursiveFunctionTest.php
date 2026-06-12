<?php

function addIds($fields)
{
    $currentParent = null;
    $globalTab = null;

    return collect($fields)->map(function ($item, $key) use (&$currentParent, &$globalTab) {
        $item['_id'] = $key + 1;

        if (optional($item)['global'] === true) {
            $globalTab = $item;
            $item['_parent_id'] = null;
            $currentParent = $item;
        }

        if ($item['parent'] === false && $item['group'] === true) {
            // Same Level Grouping
            if (optional($currentParent)['type'] == $item['type']) {
                $item['_parent_id'] = $currentParent['_parent_id'];
            } else {
                $item['_parent_id'] = $currentParent['_id'];
            }

            if (optional($item)['nested'] === true) {
                $item['_parent_id'] = $currentParent['_id'];
            }

            $currentParent = $item;
        } elseif ($item['parent'] === false && $item['group'] === false) {
            $item['_parent_id'] = $currentParent['_id'];
        } else {
            if ($globalTab && ! isset($item['global'])) {
                $item['_parent_id'] = $globalTab['_id'];

                $currentParent = $item;
            } else {
                $item['_parent_id'] = null;
            }
        }

        return $item;
    });
}

function buildTree(array &$fields, $parentId = 0)
{
    $branch = [];

    foreach ($fields as &$field) {
        if ($field['_parent_id'] == $parentId) {
            $children = buildTree($fields, $field['_id']);
            if ($children) {
                $field['fields'] = array_values($children);
            }
            $branch[$field['_id']] = $field;
            unset($field);
        }
    }

    return $branch;
}

test('addIds assigns correct IDs to global tabs', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();

    expect($array[0]['name'])->toBe('Global Tab 1')
        ->and($array[0]['_id'])->toBe(1)
        ->and($array[0]['_parent_id'])->toBeNull()
        ->and($array[0]['global'])->toBeTrue();
});

test('addIds assigns correct parent IDs to panels under global tabs', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();

    expect($array[1]['name'])->toBe('Panel 1')
        ->and($array[1]['_id'])->toBe(2)
        ->and($array[1]['_parent_id'])->toBe(1);
});

test('addIds assigns same parent ID to same-level repeaters', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();

    expect($array[2]['name'])->toBe('Repeater 1')
        ->and($array[2]['_id'])->toBe(3)
        ->and($array[2]['_parent_id'])->toBe(2)
        ->and($array[3]['name'])->toBe('Repeater 2')
        ->and($array[3]['_id'])->toBe(4)
        ->and($array[3]['_parent_id'])->toBe(2);
});

test('addIds handles nested repeaters correctly', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();

    expect($array[4]['name'])->toBe('Repeater 3')
        ->and($array[4]['_id'])->toBe(5)
        ->and($array[4]['_parent_id'])->toBe(4)
        ->and($array[4]['nested'])->toBeTrue();
});

test('addIds assigns fields inside repeater correct parent ID', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();

    expect($array[5]['name'])->toBe('Field in Repeater 3')
        ->and($array[5]['_id'])->toBe(6)
        ->and($array[5]['_parent_id'])->toBe(5);
});

test('addIds handles second global tab correctly', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();

    expect($array[7]['name'])->toBe('Global Tab 2')
        ->and($array[7]['_id'])->toBe(8)
        ->and($array[7]['_parent_id'])->toBeNull()
        ->and($array[8]['name'])->toBe('Panel 2')
        ->and($array[8]['_id'])->toBe(9)
        ->and($array[8]['_parent_id'])->toBe(8);
});

test('buildTree creates correct tree structure', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();
    $tree = buildTree($array);
    $tree = collect($tree)->values();

    expect($tree)->toHaveCount(2);
});

test('buildTree groups Panel 1 under Global Tab 1', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();
    $tree = buildTree($array);
    $tree = collect($tree)->values();

    expect($tree[0]['name'])->toBe('Global Tab 1')
        ->and($tree[0]['fields'])->toHaveCount(1)
        ->and($tree[0]['fields'][0]['name'])->toBe('Panel 1');
});

test('buildTree groups repeaters under Panel 1', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();
    $tree = buildTree($array);
    $tree = collect($tree)->values();

    expect($tree[0]['fields'][0]['fields'][0]['name'])->toBe('Repeater 1')
        ->and($tree[0]['fields'][0]['fields'][1]['name'])->toBe('Repeater 2')
        ->and($tree[0]['fields'][0]['fields'])->toHaveCount(2);
});

test('buildTree nests Repeater 3 inside Repeater 2', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();
    $tree = buildTree($array);
    $tree = collect($tree)->values();

    expect($tree[0]['fields'][0]['fields'][1]['fields'][0]['name'])->toBe('Repeater 3');
});

test('buildTree leaves Repeater 1 with empty fields', function () {
    $fields = getTestFields();
    $fields = addIds($fields);
    $array = $fields->toArray();
    $tree = buildTree($array);
    $tree = collect($tree)->values();

    expect($tree[0]['fields'][0]['fields'][0]['fields'])->toHaveCount(0);
});

function getTestFields()
{
    return [
        [
            'name' => 'Global Tab 1',
            'type' => 'tab',
            'parent' => true,
            'group' => true,
            'global' => true,
            'fields' => [],
        ],
        [
            'name' => 'Panel 1',
            'type' => 'panel',
            'parent' => true,
            'group' => true,
            'fields' => [],
        ],
        [
            'name' => 'Repeater 1',
            'type' => 'repeater',
            'parent' => false,
            'group' => true,
            'fields' => [],
        ],
        [
            'name' => 'Repeater 2',
            'type' => 'repeater',
            'parent' => false,
            'group' => true,
            'fields' => [],
        ],
        [
            'name' => 'Repeater 3',
            'type' => 'repeater',
            'parent' => false,
            'nested' => true,
            'group' => true,
            'fields' => [],
        ],
        [
            'name' => 'Field in Repeater 3',
            'type' => 'field',
            'parent' => false,
            'group' => false,
        ],
        [
            'name' => 'Field2 in Repeater 3',
            'type' => 'field',
            'parent' => false,
            'group' => false,
        ],
        [
            'name' => 'Global Tab 2',
            'type' => 'tab',
            'parent' => true,
            'group' => true,
            'global' => true,
            'fields' => [],
        ],
        [
            'name' => 'Panel 2',
            'type' => 'panel',
            'parent' => true,
            'group' => true,
            'fields' => [],
        ],
        [
            'name' => 'Repeater 4',
            'type' => 'repeater',
            'parent' => false,
            'group' => true,
            'fields' => [],
        ],
        [
            'name' => 'Tab 1',
            'type' => 'tab',
            'parent' => false,
            'group' => true,
            'fields' => [],
        ],
        [
            'name' => 'Field in Tab 1',
            'type' => 'field',
            'parent' => false,
            'group' => false,
        ],
        [
            'name' => 'Tab 2',
            'type' => 'tab',
            'parent' => false,
            'group' => true,
            'fields' => [],
        ],
        [
            'name' => 'Field in Tab 2',
            'type' => 'field',
            'parent' => false,
            'group' => false,
        ],
    ];
}
