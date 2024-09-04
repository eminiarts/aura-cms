Migrate Schema
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::create('post_relations', function (Blueprint $table) {
  $table->morphs('resource');
  $table->morphs('related');
  $table->timestamps();

  $table->index(['resource_id', 'related_id', 'related_type']);
});

Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('super_admin')->default(false);
            $table->json('permissions')->nullable();
            $table->foreignId('user_id')->nullable();
            if (config('aura.teams')) {
                $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            }
            $table->timestamps();

            if (config('aura.teams')) {
                $table->unique(['slug', 'team_id']);
            } else {
                $table->unique(['slug']);
            }
            $table->index('slug');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('group')->nullable();
            $table->foreignId('user_id')->nullable();
            if (config('aura.teams')) {
                $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            }
            $table->timestamps();

            if (config('aura.teams')) {
                $table->unique(['slug', 'team_id']);
            } else {
                $table->unique(['slug']);
            }
            $table->index('slug');
        });
```

Create roles and permissions from `posts` table to `roles` and `permissions` table

```php
use Illuminate\Support\Facades\DB;

// Migrate roles
$rolePosts = DB::table('posts')->where('type', 'role')->get();

foreach ($rolePosts as $post) {
    // Fetch associated meta
    $meta = DB::table('post_meta')->where('post_id', $post->id)->pluck('value', 'key');

    // Prepare slug (if you have a specific slug generation strategy, adjust this)
    $slug = Str::slug($post->title);

    // Create role in the roles table
    DB::table('roles')->insert([
        'name' => $post->title,
        'slug' => $slug,
        'description' => $post->content,
        'super_admin' => $meta->get('super_admin', false), // If you have a "super_admin" meta key
        'permissions' => json_encode($meta->get('permissions', [])), // If you have a "permissions" meta key
        'user_id' => $post->user_id ?? null, // Adjust this if user_id is stored elsewhere
        'team_id' => $post->team_id ?? null, // Adjust this if team_id is stored elsewhere
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Migrate permissions
$permissionPosts = DB::table('posts')->where('type', 'permission')->get();

foreach ($permissionPosts as $post) {
    // Fetch associated meta
    $meta = DB::table('post_meta')->where('post_id', $post->id)->pluck('value', 'key');

    // Prepare slug (if you have a specific slug generation strategy, adjust this)
    $slug = Str::slug($post->title);

    // Create permission in the permissions table
    DB::table('permissions')->insert([
        'name' => $post->title,
        'slug' => $slug,
        'description' => $post->content,
        'group' => $meta->get('group', null), // If you have a "group" meta key
        'user_id' => $post->user_id ?? null, // Adjust this if user_id is stored elsewhere
        'team_id' => $post->team_id ?? null, // Adjust this if team_id is stored elsewhere
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

echo "Migration complete!";

```

Migrate user roles from old to new
from `posts` table to `roles` table

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Do it manually for each team
$team_id = 1;

$roleMappings = DB::table("roles")->where("team_id", $team_id)->pluck("id", "slug"); // Mapping of slug to new role IDs
$usersWithRoles = DB::table("user_meta")
  ->where("key", "roles")
  ->where("team_id", $team_id)
  ->get();

foreach ($usersWithRoles as $userMeta) {
  $oldRoleId = $userMeta->value; // This is the old role ID from the `posts` table

  // Fetch the corresponding post (old role)
  $oldRolePost = DB::table("posts")
    ->where("id", $oldRoleId)
    ->where("type", "role")
    ->where("team_id", $team_id)
    ->first();

  if ($oldRolePost) {
    $newRoleSlug = Str::slug($oldRolePost->title); // Generate the slug based on the old role title

    if (isset($roleMappings[$newRoleSlug])) {
      $newRoleId = $roleMappings[$newRoleSlug]; // Get the new role ID from the roles table

      // dump($newRoleId, $oldRoleId);
      // Insert into post_relations
      DB::table("post_relations")->insert([
        "resource_type" => "Aura\\Base\\Resources\\Role",
        "resource_id" => $newRoleId, // New role ID from the roles table
        "related_type" => "Aura\\Base\\Resources\\User",
        "related_id" => $userMeta->user_id, // User ID from the user_meta table
        "created_at" => now(),
        "updated_at" => now()
      ]);
    }
  }
}

echo "User-role connections migrated successfully!";
```
