<?php

namespace Aura\Base\Traits\Concerns;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

trait AuraResourceIdentity
{
    public static $pluralName = null;

    public static $singularName = null;

    protected static ?string $name = null;

    protected static ?string $slug = null;

    protected static string $type = 'Resource';

    /** Avatar markup for use outside Blade (Field::display() etc.). Empty when the resource opts out. */
    public function avatarHtml(string $size = 'sm'): string
    {
        if (! $this->avatarShape()) {
            return '';
        }

        return Blade::render('<x-aura::avatar :record="$record" :size="$size" />', ['record' => $this, 'size' => $size]);
    }

    /**
     * Attachment id of the record's own image, if the resource has an Image
     * field named avatar, logo, image or photo. The avatar component prefers
     * it over initials.
     */
    public function avatarImageId(): int|string|null
    {
        foreach (['avatar', 'logo', 'image', 'photo'] as $slug) {
            $field = $this->fieldBySlug($slug);

            if (! $field || ($field['type'] ?? null) !== 'Aura\\Base\\Fields\\Image') {
                continue;
            }

            $value = $this->{$slug} ?? null;

            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            return $value ?: null;
        }

        return null;
    }

    /** First letter of the first and last word of the title ("Atlas Components" → "AC"). */
    public function avatarInitials(): string
    {
        $words = preg_split('/\s+/u', trim((string) $this->title()), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_values(array_filter($words, fn ($word) => preg_match('/^[\p{L}\p{N}]/u', $word)));

        if ($words === []) {
            return '?';
        }

        $initials = Str::substr($words[0], 0, 1);

        if (count($words) > 1) {
            $initials .= Str::substr($words[array_key_last($words)], 0, 1);
        }

        return Str::upper($initials);
    }

    /**
     * Entity avatar shape: 'square' for organisations and things, 'circle'
     * for people, null to show no avatar. Rendered before the record name in
     * tables, relation chips and the record header.
     */
    public function avatarShape(): ?string
    {
        return 'square';
    }

    /**
     * Tint derived from the title, so the same record always gets the same color. A trailing
     * " (#id)" (Role, the generic fallback title) is left out, as in the initials: the tint
     * follows the name, so "Admin (#1)" and "Admin (#2)" share it.
     */
    public function avatarTone(): string
    {
        $tones = static::avatarTones();
        $name = preg_replace('/\s*\(#\d+\)$/u', '', trim((string) $this->title()));

        return $tones[crc32(Str::lower(trim($name))) % count($tones)];
    }

    /**
     * Tints an avatar may use: eight Tailwind families the status and category
     * tags do not use (those hold blue, orange, teal, gray, yellow, green and
     * red). The warm band stays with the tags, so an avatar never echoes the
     * chip in its row. Red stays reserved for errors.
     */
    public static function avatarTones(): array
    {
        return ['indigo', 'purple', 'fuchsia', 'pink', 'rose', 'violet', 'lime', 'cyan'];
    }

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
        // Mirrors Aura::findResourceBySlug(), which falls back to the class
        // basename. Without it a resource declaring neither $slug nor $name
        // would produce an empty slug and therefore an empty /admin/ route.
        return static::$slug ?? Str::slug(static::$name ?? class_basename(static::class));
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
