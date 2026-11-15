<?php

namespace Aura\Base\Resources;

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Illuminate\Support\Str;

class Permission extends Resource
{
    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'permission';

    public static bool $usesMeta = false;

    protected static $dropdown = 'Users';

    protected static ?string $group = 'Aura';

    protected static ?int $sort = 3;

    protected $table = 'permissions';

    protected static bool $title = false;

    protected static string $type = 'Permission';

    // Config rows, not entities: no avatar.
    public function avatarShape(): ?string
    {
        return null;
    }

    /** The group as the list's tag (escaped: views print display values raw). */
    public static function displayGroupField($value, self $model): string
    {
        if (blank($value)) {
            return '';
        }

        return '<span class="aura-tag">'.e(static::displayLabel($value)).'</span>';
    }

    /**
     * A generated permission name or group in sentence case: "View Any Media Library" is "View all media
     * library", "Media-Folders" is "Media folders". Words in capitals (API) keep them. The policy terms of
     * the generated names read as what they grant (GenerateResourcePermissions, ResourcePolicy): viewAny is
     * "View all …", forceDelete "Permanently delete …", and scope, which limits view, update and delete to
     * the records the user owns (user_id), "Limit … to own records". Display only.
     */
    public static function displayLabel(?string $value): string
    {
        $words = preg_split('/[\s_-]+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $words = array_map(
            fn (string $word) => preg_match('/^\p{Lu}{2,}$/u', $word) ? $word : Str::lower($word),
            $words,
        );

        $label = implode(' ', $words);

        $label = match (true) {
            str_starts_with($label, 'view any ') => 'view all '.substr($label, 9),
            str_starts_with($label, 'force delete ') => 'permanently delete '.substr($label, 13),
            str_starts_with($label, 'scope ') => 'limit '.substr($label, 6).' to own records',
            default => $label,
        };

        return Str::ucfirst($label);
    }

    /** Escaped: views print display values raw. */
    public static function displayNameField($value, self $model): string
    {
        return e(static::displayLabel($value));
    }

    public static function getFields(): array
    {
        return [

            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                // Shown in sentence case ("Create media library"); the stored name stays as generated.
                'display' => [self::class, 'displayNameField'],
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'slug',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Group',
                'slug' => 'group',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'display' => [self::class, 'displayGroupField'],
                'conditional_logic' => [],
                'options' => [
                    'Invoice' => 'Invoice',
                    'Permission' => 'Permission',
                    'Post' => 'Post',
                    'Project' => 'Project',
                    'Role' => 'Role',
                    'User' => 'User',
                ],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }

    public static function getGroupOptions()
    {
        return collect(Aura::getResources())->mapWithKeys(fn ($item) => [$item => $item])->toArray();
    }

    public function getIcon()
    {
        return view('aura::components.icon.permission')->render();
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
