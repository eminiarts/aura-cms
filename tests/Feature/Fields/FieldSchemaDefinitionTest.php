<?php

use Aura\Base\Commands\CreateResourceMigration;
use Aura\Base\Fields\Number;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('number fields declare portable integer and decimal schema columns', function () {
    $number = new Number;
    $integer = $number->columnDefinition([
        'slug' => 'quantity',
        'number_type' => 'integer',
        'scale' => 2,
    ]);
    $decimal = $number->columnDefinition([
        'slug' => 'amount',
        'number_type' => 'decimal',
        'precision' => 15,
        'scale' => 4,
    ]);

    expect($integer->type)->toBe('integer')
        ->and($integer->arguments)->toBe([])
        ->and($integer->toMigration('quantity'))->toBe("\$table->integer('quantity')->nullable()")
        ->and($decimal->type)->toBe('decimal')
        ->and($decimal->arguments)->toBe([15, 4])
        ->and($decimal->toMigration('amount'))->toContain("\$table->text('amount')")
        ->and($decimal->toMigration('amount'))->toContain("\$table->decimal('amount', 15, 4)");

    Schema::create('core_10_schema_values', function (Blueprint $table) use ($decimal, $integer) {
        $table->id();
        $integer->addTo($table, 'quantity');
        $decimal->addTo($table, 'amount');
    });

    DB::table('core_10_schema_values')->insert([
        'quantity' => -2,
        'amount' => '1234.5678',
    ]);

    expect(DB::table('core_10_schema_values')->value('quantity'))->toBe(-2)
        ->and((string) DB::table('core_10_schema_values')->value('amount'))->toBe('1234.5678');
});

test('configured large numbers use exact storage on sqlite', function () {
    $number = new Number;
    $integer = $number->columnDefinition([
        'slug' => 'large_integer',
        'number_type' => 'integer',
        'precision' => 65,
    ]);
    $decimal = $number->columnDefinition([
        'slug' => 'large_decimal',
        'number_type' => 'decimal',
        'precision' => 65,
        'scale' => 30,
    ]);

    Schema::create('core_10_exact_schema_values', function (Blueprint $table) use ($decimal, $integer) {
        $table->id();
        $integer->addTo($table, 'large_integer');
        $decimal->addTo($table, 'large_decimal');
    });

    $largeInteger = '12345678901234567890123456789012345678901234567890123456789012345';
    $largeDecimal = '12345678901234567890123456789012345.123456789012345678901234567890';

    DB::table('core_10_exact_schema_values')->insert([
        'large_integer' => $largeInteger,
        'large_decimal' => $largeDecimal,
    ]);

    expect($integer->type)->toBe('decimal')
        ->and($integer->arguments)->toBe([65, 0])
        ->and(DB::table('core_10_exact_schema_values')->value('large_integer'))->toBe($largeInteger)
        ->and(DB::table('core_10_exact_schema_values')->value('large_decimal'))->toBe($largeDecimal);
});

test('configured large numbers use native exact decimal storage on mysql when available', function () {
    $database = getenv('AURA_TEST_MYSQL_DATABASE') ?: null;

    if (! $database) {
        $this->markTestSkipped('Set AURA_TEST_MYSQL_DATABASE to run the MySQL exactness contract.');
    }

    $connection = 'core_10_mysql';
    $originalDefault = config('database.default');
    $tableName = 'aura_core_10_exact_'.getmypid();

    config()->set("database.connections.{$connection}", [
        'driver' => 'mysql',
        'host' => getenv('AURA_TEST_MYSQL_HOST') ?: '127.0.0.1',
        'port' => getenv('AURA_TEST_MYSQL_PORT') ?: '3306',
        'database' => $database,
        'username' => getenv('AURA_TEST_MYSQL_USERNAME') ?: 'root',
        'password' => getenv('AURA_TEST_MYSQL_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]);
    config()->set('database.default', $connection);
    DB::purge($connection);

    try {
        $number = new Number;
        $integer = $number->columnDefinition([
            'slug' => 'large_integer',
            'number_type' => 'integer',
            'precision' => 65,
        ]);
        $decimal = $number->columnDefinition([
            'slug' => 'large_decimal',
            'number_type' => 'decimal',
            'precision' => 65,
            'scale' => 30,
        ]);

        Schema::create($tableName, function (Blueprint $table) use ($decimal, $integer) {
            $integer->addTo($table, 'large_integer');
            $decimal->addTo($table, 'large_decimal');
        });

        $largeInteger = '12345678901234567890123456789012345678901234567890123456789012345';
        $largeDecimal = '12345678901234567890123456789012345.123456789012345678901234567890';
        DB::table($tableName)->insert([
            'large_integer' => $largeInteger,
            'large_decimal' => $largeDecimal,
        ]);

        expect(DB::table($tableName)->value('large_integer'))->toBe($largeInteger)
            ->and(DB::table($tableName)->value('large_decimal'))->toBe($largeDecimal);
    } finally {
        Schema::dropIfExists($tableName);
        DB::disconnect($connection);
        config()->set('database.default', $originalDefault);
    }
});

test('every migration generator includes decimal precision and scale', function (string $generatorClass) {
    $generator = new $generatorClass(app(Filesystem::class));
    $method = new ReflectionMethod($generatorClass, 'generateColumn');

    $migration = $method->invoke($generator, [
        'slug' => 'amount',
        'type' => Number::class,
        'number_type' => 'decimal',
        'precision' => 18,
        'scale' => 6,
    ]);

    expect($migration)->toContain("\$table->decimal('amount', 18, 6)");
})->with([
    CreateResourceMigration::class,
    CreateDatabaseMigration::class,
    ModifyDatabaseMigration::class,
]);

test('resource editor migrations change an existing integer column to its decimal definition', function () {
    $generator = new CreateDatabaseMigration(app(Filesystem::class));
    $up = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
    $down = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
    $fields = collect([
        [
            'old' => [
                'slug' => 'amount',
                'type' => Number::class,
                'number_type' => 'integer',
            ],
            'new' => [
                'slug' => 'amount',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 19,
                'scale' => 2,
            ],
        ],
    ]);

    expect($up->invoke($generator, $fields, 'update'))
        ->toContain("\$table->decimal('amount', 19, 2)")
        ->toContain(')->nullable()->change()')
        ->and($down->invoke($generator, $fields, 'update'))
        ->toContain("\$table->integer('amount')->nullable()->change()");
});
