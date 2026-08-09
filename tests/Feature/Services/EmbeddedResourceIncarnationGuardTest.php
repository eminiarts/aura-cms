<?php

use Aura\Base\BaseResource;
use Aura\Base\Exceptions\MissingEmbeddedResourceIncarnationGuard;
use Aura\Base\Services\EmbeddedResourceIncarnationGuard;
use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Core12QuotedGuardResource extends BaseResource
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'select';

    protected $table = 'core12 guarded-owners';

    public static function getFields(): array
    {
        return [];
    }
}

beforeEach(function (): void {
    Schema::create('core12 guarded-owners', function (Blueprint $table): void {
        $table->string('select')->primary();
        $table->string('title')->nullable();
    });
});

it('installs quoted SQLite guards idempotently and invalidates raw row identity reuse', function () {
    $guard = app(EmbeddedResourceIncarnationGuard::class);
    $guard->install(Core12QuotedGuardResource::class);
    $guard->install(Core12QuotedGuardResource::class);

    expect($guard->isInstalled(new Core12QuotedGuardResource))->toBeTrue()
        ->and(DB::table('sqlite_master')
            ->where('type', 'trigger')
            ->where('tbl_name', 'core12 guarded-owners')
            ->count())->toBe(3);

    DB::table('core12 guarded-owners')->insert([
        'select' => 'quoted-key',
        'title' => 'Original',
    ]);
    $resource = Core12QuotedGuardResource::query()->findOrFail('quoted-key');
    $incarnations = app(EmbeddedResourceIncarnationStore::class);
    $token = $incarnations->token($resource);
    $version = $incarnations->version($resource);
    $attributes = $resource->getRawOriginal();

    DB::table('core12 guarded-owners')->where('select', 'quoted-key')->delete();
    DB::table('core12 guarded-owners')->insert($attributes);
    $incarnations->flush();

    expect($incarnations->token($resource))->toBe($token)
        ->and($incarnations->version($resource))->toBeGreaterThan($version);

    $version = $incarnations->version($resource);
    DB::statement(
        'insert or replace into "core12 guarded-owners" ("select", "title") values (?, ?)',
        ['quoted-key', 'Original'],
    );
    $incarnations->flush();

    expect($incarnations->version($resource))->toBeGreaterThan($version);

    $guard->uninstall(Core12QuotedGuardResource::class);
    $guard->uninstall(Core12QuotedGuardResource::class);

    expect($guard->isInstalled(new Core12QuotedGuardResource))->toBeFalse();
});

it('invalidates a destination identity when a primary key is moved into it', function () {
    $guard = app(EmbeddedResourceIncarnationGuard::class);
    $guard->install(Core12QuotedGuardResource::class);
    DB::table('core12 guarded-owners')->insert([
        'select' => 'destination-key',
        'title' => 'Original',
    ]);

    $destination = Core12QuotedGuardResource::query()->findOrFail('destination-key');
    $incarnations = app(EmbeddedResourceIncarnationStore::class);
    $version = $incarnations->version($destination);

    $guard->uninstall($destination);
    DB::table('core12 guarded-owners')->where('select', 'destination-key')->delete();
    $guard->install($destination);

    DB::table('core12 guarded-owners')->insert([
        'select' => 'source-key',
        'title' => 'Original',
    ]);
    DB::table('core12 guarded-owners')
        ->where('select', 'source-key')
        ->update(['select' => 'destination-key']);
    $incarnations->flush();

    expect($incarnations->version($destination))->toBeGreaterThan($version);
});

it('advances identity when an owner created before guard installation is replaced before first prime', function () {
    DB::table('core12 guarded-owners')->insert([
        'select' => 'unprimed-key',
        'title' => 'Byte-identical owner',
    ]);
    $attributes = DB::table('core12 guarded-owners')->where('select', 'unprimed-key')->first();
    $guard = app(EmbeddedResourceIncarnationGuard::class);
    $guard->install(Core12QuotedGuardResource::class);

    DB::table('core12 guarded-owners')->where('select', 'unprimed-key')->delete();
    DB::table('core12 guarded-owners')->insert((array) $attributes);

    $resource = Core12QuotedGuardResource::query()->findOrFail('unprimed-key');
    $incarnations = app(EmbeddedResourceIncarnationStore::class);

    expect($incarnations->version($resource))->toBeGreaterThan(1);
});

it('fails closed when a resource guard is absent', function () {
    $resource = new Core12QuotedGuardResource;
    $resource->setAttribute($resource->getKeyName(), 'missing-guard');
    $resource->exists = true;

    expect(fn () => app(EmbeddedResourceIncarnationStore::class)->token($resource))
        ->toThrow(MissingEmbeddedResourceIncarnationGuard::class)
        ->and(DB::table('sqlite_master')
            ->where('type', 'trigger')
            ->where('tbl_name', 'core12 guarded-owners')
            ->count())->toBe(0);
});

it('propagates database write failures while installing a guard', function () {
    DB::statement('PRAGMA query_only = ON');

    try {
        expect(fn () => app(EmbeddedResourceIncarnationGuard::class)->install(Core12QuotedGuardResource::class))
            ->toThrow(QueryException::class);
    } finally {
        DB::statement('PRAGMA query_only = OFF');
    }

    expect(app(EmbeddedResourceIncarnationGuard::class)->isInstalled(new Core12QuotedGuardResource))
        ->toBeFalse();
});
