<?php

namespace Aura\Base\Database\Seeders;

use Aura\Base\Resources\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the two Global Roles that make up the base Role Catalog.
 *
 * A fresh install — in either Teams-on or Teams-off mode — must have an `admin`
 * (Super Admin) and a `user` Global Role so that registration and team creation
 * work without hand-seeding. Global Roles carry team_id = null (Teams-on) or are
 * simply the only rows (Teams-off). The seeder is idempotent so it is safe to run
 * from the install migration, the install command, or a database seed.
 */
class RoleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        static::seed();
    }

    /**
     * The Global Roles the package guarantees on a fresh install.
     *
     * Order matters: `admin` is seeded first so it keeps the lowest id, which
     * preserves the long-standing "the admin role is the first role" assumption
     * in Teams-off installs.
     */
    public static function seed(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $hasTeamColumn = Schema::hasColumn('roles', 'team_id');

        static::seedRole('admin', $hasTeamColumn);
        static::seedRole('user', $hasTeamColumn);
    }

    protected static function seedRole(string $slug, bool $hasTeamColumn): void
    {
        $exists = DB::table('roles')
            ->where('slug', $slug)
            ->when($hasTeamColumn, fn ($query) => $query->whereNull('team_id'))
            ->exists();

        if ($exists) {
            return;
        }

        // Shared catalog defaults, so the seeded rows and the self-healed rows
        // (Role::firstOrCreateCatalogRole) stay identical.
        $defaults = Role::catalogDefaults($slug);

        $row = [
            'name' => $defaults['name'],
            'slug' => $defaults['slug'],
            'description' => $defaults['description'],
            'super_admin' => $defaults['super_admin'],
            'permissions' => json_encode($defaults['permissions']),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($hasTeamColumn) {
            $row['team_id'] = null;
        }

        DB::table('roles')->insert($row);
    }
}
