<?php

namespace Aura\Base\Contracts;

/**
 * Capability contract shared by Resource and the public BaseResource table
 * foundation. Return types remain intentionally open for host-app backward
 * compatibility with existing resource declarations.
 */
interface TableResource extends DefinesFields
{
    public function fieldBySlug($slug);

    public function fieldClassBySlug($slug);

    public function getActions();

    public function getBulkActions();

    public function isMetaField($key): bool;

    public function isTableField($key): bool;
}
