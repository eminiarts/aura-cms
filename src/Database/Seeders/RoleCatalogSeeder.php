<?php

namespace Aura\Base\Database\Seeders;

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

        static::seedRole('admin', 'Admin', 'Admin can perform everything.', true, $hasTeamColumn);
        static::seedRole('user', 'User', 'Default role with minimal permissions.', false, $hasTeamColumn);
    }

    protected static function seedRole(string $slug, string $name, string $description, bool $superAdmin, bool $hasTeamColumn): void
    {
        $exists = DB::table('roles')
            ->where('slug', $slug)
            ->when($hasTeamColumn, fn ($query) => $query->whereNull('team_id'))
            ->exists();

        if ($exists) {
            return;
        }

        $row = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'super_admin' => $superAdmin,
            'permissions' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($hasTeamColumn) {
            $row['team_id'] = null;
        }

        DB::table('roles')->insert($row);
    }
}
