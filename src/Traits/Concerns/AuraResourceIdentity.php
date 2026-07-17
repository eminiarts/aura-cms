<?php

namespace Aura\Base\Traits\Concerns;

use Illuminate\Support\Str;

trait AuraResourceIdentity
{
    public static $pluralName = null;

    public static $singularName = null;

    protected static ?string $name = null;

    protected static ?string $slug = null;

    protected static string $type = 'Resource';

    public static function getName(): ?string
    {
        return static::$name;
    }

    public static function getPluralName(): string
    {

        return static::$pluralName ?? str(static::$type)->plural();
    }

    public static function getSlug(): string
    {
        return static::$slug ?? Str::slug(static::$name);
    }

    public static function getType(): string
    {
        return static::$type;
    }

    public function isAppResource()
    {
        return Str::startsWith(get_class($this), 'App');
    }

    public function isVendorResource()
    {
        return ! $this->isAppResource();
    }

    public function pluralName()
    {
        return __(static::$pluralName ?? Str::plural($this->singularName()));
    }

    public function singularName()
    {
        return static::$singularName ?? Str::title(static::$slug);
    }

    public function title()
    {
        if (optional($this)->id) {
            return __($this->getType())." (#{$this->id})";
        }
    }
}
