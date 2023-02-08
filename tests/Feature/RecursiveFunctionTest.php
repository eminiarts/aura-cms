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

test('recursive field function', function () {
    $fields = [
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

    $fields = addIds($fields);

    $array = $fields->toArray();

    $this->assertEquals($array[0]['name'], 'Global Tab 1');
    $this->assertEquals($array[0]['_id'], 1);
    $this->assertEquals($array[0]['_parent_id'], null);
    $this->assertEquals($array[0]['global'], true);
    $this->assertEquals($array[1]['name'], 'Panel 1');
    $this->assertEquals($array[1]['_id'], 2);
    $this->assertEquals($array[1]['_parent_id'], 1);
    $this->assertEquals($array[2]['name'], 'Repeater 1');
    $this->assertEquals($array[2]['_id'], 3);
    $this->assertEquals($array[2]['_parent_id'], 2);
    $this->assertEquals($array[3]['name'], 'Repeater 2');
    $this->assertEquals($array[3]['_id'], 4);
    $this->assertEquals($array[3]['_parent_id'], 2);
    $this->assertEquals($array[4]['name'], 'Repeater 3');
    $this->assertEquals($array[4]['_id'], 5);
    $this->assertEquals($array[4]['_parent_id'], 4);
    $this->assertEquals($array[4]['nested'], true);
    $this->assertEquals($array[5]['name'], 'Field in Repeater 3');
    $this->assertEquals($array[5]['_id'], 6);
    $this->assertEquals($array[5]['_parent_id'], 5);
    $this->assertEquals($array[7]['name'], 'Global Tab 2');
    $this->assertEquals($array[7]['_id'], 8);
    $this->assertEquals($array[7]['_parent_id'], null);
    $this->assertEquals($array[8]['name'], 'Panel 2');
    $this->assertEquals($array[8]['_id'], 9);
    $this->assertEquals($array[8]['_parent_id'], 8);

    $tree = buildTree($array);

    $tree = collect($tree)->values();

    $this->assertCount(2, $tree);
    $this->assertCount(1, $tree[0]['fields']);
    $this->assertEquals($tree[0]['name'], 'Global Tab 1');
    $this->assertEquals($tree[0]['fields'][0]['name'], 'Panel 1');
    $this->assertEquals($tree[0]['fields'][0]['fields'][0]['name'], 'Repeater 1');
    $this->assertEquals($tree[0]['fields'][0]['fields'][1]['name'], 'Repeater 2');
    $this->assertEquals($tree[0]['fields'][0]['fields'][1]['fields'][0]['name'], 'Repeater 3');
    $this->assertCount(2, $tree[0]['fields'][0]['fields']);
    $this->assertCount(0, $tree[0]['fields'][0]['fields'][0]['fields']);
});
