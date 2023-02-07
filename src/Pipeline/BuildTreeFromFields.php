<?php

namespace Eminiarts\Aura\Aura\Pipeline;

use Closure;

class BuildTreeFromFields implements Pipe
{
    public function buildTree(array &$fields, $parentId = 0)
    {
        $branch = [];

        foreach ($fields as &$field) {
            if ($field['_parent_id'] == $parentId) {
                $children = $this->buildTree($fields, $field['_id']);
                if ($children) {
                    $field['fields'] = array_values($children);
                }
                $branch[$field['_id']] = $field;
                unset($field);
            }
        }

        return $branch;
    }

    public function handle($fields, Closure $next)
    {
        $array = $fields->toArray();

        $tree = $this->buildTree($array);

        $tree = array_values($tree);

        return  $next($tree);
    }
}
