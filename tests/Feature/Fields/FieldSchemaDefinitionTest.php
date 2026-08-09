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
        ->and($decimal->toMigration('amount'))->toBe("\$table->decimal('amount', 15, 4)->nullable()");

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
        ->toContain("\$table->decimal('amount', 19, 2)->nullable()->change()")
        ->and($down->invoke($generator, $fields, 'update'))
        ->toContain("\$table->integer('amount')->nullable()->change()");
});
