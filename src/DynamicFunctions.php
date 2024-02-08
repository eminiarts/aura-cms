<?php

namespace Aura\Base;

class DynamicFunctions
{
    public $closures = [];

    public function add($callback)
    {
        $reflection = new \ReflectionFunction($callback);
        $file = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();
        $hash = md5("{$file}_{$startLine}_{$endLine}");

        $this->closures[$hash][] = $callback;

        return $hash;
    }

    public function call($hash)
    {
        if (!isset($this->closures[$hash])) {
            throw new \Exception("No registered closures for hash: {$hash}");
        }

        foreach ($this->closures[$hash] as $callback) {
            return $callback();
        }
    }

}
