<?php

use Aura\Base\Commands\CreateResourceMigration;
use Aura\Base\Fields\Number;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Schema\FieldColumn;
use Aura\Base\Schema\SchemaUpdatePlan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

test('structured schema plans preserve driver-specific decimal precision and scale', function () {
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-plan-');
    $definition = new FieldColumn('decimal', [12, 4], driverTypes: ['sqlite' => 'text']);
    $plan = new SchemaUpdatePlan('core_10_plan_values', ['amount' => $definition]);

    File::put($path, $plan->embedIn("<?php\n\nreturn new class {};\n"));

    try {
        $restored = SchemaUpdatePlan::fromMigrationFile($path);

        expect($restored->columns['amount']->forDriver('mysql')->type)->toBe('decimal')
            ->and($restored->columns['amount']->forDriver('mysql')->arguments)->toBe([12, 4])
            ->and($restored->columns['amount']->forDriver('pgsql')->arguments)->toBe([12, 4])
            ->and($restored->columns['amount']->forDriver('sqlite')->type)->toBe('text')
            ->and($restored->columns['amount']->forDriver('sqlite')->arguments)->toBe([]);
    } finally {
        File::delete($path);
    }
});

test('schema update preflights every conversion before additions and drops', function () {
    $tableName = 'core_10_preflight_values';
    Schema::create($tableName, function (Blueprint $table) {
        $table->id();
        $table->string('amount')->nullable();
        $table->string('drop_after_failure')->nullable();
    });
    DB::table($tableName)->insert(['amount' => 'not-an-integer']);

    $path = tempnam(sys_get_temp_dir(), 'aura-core10-preflight-');
    $plan = new SchemaUpdatePlan($tableName, [
        'amount' => new FieldColumn('integer'),
        'added_before_failure' => new FieldColumn('string'),
    ]);
    File::put($path, $plan->embedIn("<?php\n\nreturn new class {};\n"));

    try {
        $exitCode = Artisan::call('aura:schema-update', [
            'migration' => $path,
            '--no-interaction' => true,
        ]);

        expect($exitCode)->not->toBe(0)
            ->and(Artisan::output())->toContain('Refusing lossy conversion')
            ->and(Schema::hasColumn($tableName, 'added_before_failure'))->toBeFalse()
            ->and(Schema::hasColumn($tableName, 'drop_after_failure'))->toBeTrue()
            ->and(DB::table($tableName)->value('amount'))->toBe('not-an-integer');
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});

test('schema update reports missing and unparseable migration plans as failures', function () {
    $missing = sys_get_temp_dir().'/aura-core10-missing-'.uniqid().'.php';
    $invalid = tempnam(sys_get_temp_dir(), 'aura-core10-invalid-');
    $nested = tempnam(sys_get_temp_dir(), 'aura-core10-nested-');
    File::put($invalid, "<?php\n\nreturn new class { public function broken( };\n");
    File::put($nested, <<<'PHP'
<?php

return new class
{
    public function up(): void
    {
        if (true) {
            // A syntactically valid nested migration without a structured plan.
        }
    }
};
PHP);

    try {
        expect(Artisan::call('aura:schema-update', [
            'migration' => $missing,
            '--no-interaction' => true,
        ]))->not->toBe(0)
            ->and(Artisan::call('aura:schema-update', [
                'migration' => $invalid,
                '--no-interaction' => true,
            ]))->not->toBe(0)
            ->and(Artisan::call('aura:schema-update', [
                'migration' => $nested,
                '--no-interaction' => true,
            ]))->not->toBe(0);
    } finally {
        File::delete([$invalid, $nested]);
    }
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
        Schema::create($tableName, fn (Blueprint $table) => $table->id());
        $generator = new CreateDatabaseMigration(app(Filesystem::class));
        $generate = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
        $generateDown = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
        $update = new ReflectionMethod(CreateDatabaseMigration::class, 'updateMigrationContent');
        $fields = collect([
            [
                'slug' => 'large_integer',
                'type' => Number::class,
                'number_type' => 'integer',
                'precision' => 65,
            ],
            [
                'slug' => 'large_decimal',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 65,
                'scale' => 30,
            ],
        ]);
        $up = $generate->invoke($generator, $fields, 'add');
        $down = $generateDown->invoke($generator, $fields, 'add');
        $template = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }
};
PHP);
        $content = $update->invoke($generator, $template, $up, '', '', $down, '', '', '');
        $path = tempnam(sys_get_temp_dir(), 'aura-core10-mysql-migration-');
        File::put($path, $content);
        $migration = require $path;
        $migration->up();

        $largeInteger = '12345678901234567890123456789012345678901234567890123456789012345';
        $largeDecimal = '12345678901234567890123456789012345.123456789012345678901234567890';
        DB::table($tableName)->insert([
            'large_integer' => $largeInteger,
            'large_decimal' => $largeDecimal,
        ]);

        expect(DB::table($tableName)->value('large_integer'))->toBe($largeInteger)
            ->and(DB::table($tableName)->value('large_decimal'))->toBe($largeDecimal)
            ->and(Schema::getColumnType($tableName, 'large_integer'))->toBe('decimal')
            ->and(Schema::getColumnType($tableName, 'large_decimal'))->toBe('decimal');

        $migration->down();
    } finally {
        if (isset($path)) {
            File::delete($path);
        }

        Schema::dropIfExists($tableName);
        DB::disconnect($connection);
        config()->set('database.default', $originalDefault);
    }
});

test('structured schema updates retain decimals and preflight every mutation on real databases', function (string $driver) {
    $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
    $database = getenv("AURA_TEST_{$prefix}_DATABASE") ?: null;

    if (! $database) {
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} schema-update contract.");
    }

    $connection = 'core_10_schema_update_'.$driver;
    $originalDefault = config('database.default');
    $tableName = 'aura_c10_update_'.$driver.'_'.getmypid();
    $failureTable = 'aura_c10_failure_'.$driver.'_'.getmypid();
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-schema-update-');
    $failurePath = tempnam(sys_get_temp_dir(), 'aura-core10-schema-failure-');
    $configuration = [
        'driver' => $driver,
        'host' => getenv("AURA_TEST_{$prefix}_HOST") ?: '127.0.0.1',
        'port' => getenv("AURA_TEST_{$prefix}_PORT") ?: ($driver === 'mysql' ? '3306' : '5432'),
        'database' => $database,
        'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: ($driver === 'mysql' ? 'root' : getenv('USER')),
        'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
        'prefix' => '',
    ];

    if ($driver === 'mysql') {
        $configuration += [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => true,
        ];
    } else {
        $configuration += ['search_path' => 'public'];
    }

    config()->set("database.connections.{$connection}", $configuration);
    config()->set('database.default', $connection);
    DB::purge($connection);

    try {
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 4)->nullable();
        });
        DB::table($tableName)->insert(['amount' => '12345678.9012']);
        $plan = new SchemaUpdatePlan($tableName, [
            'amount' => new FieldColumn('decimal', [12, 4], driverTypes: ['sqlite' => 'text']),
            'added_after_preflight' => new FieldColumn('string'),
        ]);
        File::put($path, $plan->embedIn("<?php\n\nreturn new class {};\n"));

        expect(Artisan::call('aura:schema-update', [
            'migration' => $path,
            '--no-interaction' => true,
        ]))->toBe(0)
            ->and(Schema::hasColumn($tableName, 'added_after_preflight'))->toBeTrue()
            ->and(DB::table($tableName)->value('amount'))->toBe('12345678.9012');

        $amount = collect(Schema::getColumns($tableName))->firstWhere('name', 'amount');

        expect(strtolower($amount['type'] ?? ''))->toContain($driver === 'mysql' ? 'decimal(12,4)' : 'numeric(12,4)');

        Schema::create($failureTable, function (Blueprint $table) {
            $table->id();
            $table->string('amount')->nullable();
            $table->string('drop_after_failure')->nullable();
        });
        DB::table($failureTable)->insert(['amount' => 'not-an-integer']);
        $failurePlan = new SchemaUpdatePlan($failureTable, [
            'amount' => new FieldColumn('integer'),
            'added_before_failure' => new FieldColumn('string'),
        ]);
        File::put($failurePath, $failurePlan->embedIn("<?php\n\nreturn new class {};\n"));

        expect(Artisan::call('aura:schema-update', [
            'migration' => $failurePath,
            '--no-interaction' => true,
        ]))->not->toBe(0)
            ->and(Schema::hasColumn($failureTable, 'added_before_failure'))->toBeFalse()
            ->and(Schema::hasColumn($failureTable, 'drop_after_failure'))->toBeTrue();
    } finally {
        Schema::dropIfExists($failureTable);
        Schema::dropIfExists($tableName);
        File::delete([$path, $failurePath]);
        DB::disconnect($connection);
        config()->set('database.default', $originalDefault);
    }
})->with(['mysql', 'pgsql']);

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

test('generated resource editor migration code executes against sqlite', function () {
    $tableName = 'core_10_generated_migration_values';
    Schema::create($tableName, fn (Blueprint $table) => $table->id());

    $generator = new CreateDatabaseMigration(app(Filesystem::class));
    $generate = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
    $generateDown = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
    $update = new ReflectionMethod(CreateDatabaseMigration::class, 'updateMigrationContent');
    $fields = collect([[
        'slug' => 'amount',
        'type' => Number::class,
        'number_type' => 'decimal',
        'precision' => 65,
        'scale' => 30,
    ]]);
    $up = $generate->invoke($generator, $fields, 'add');
    $down = $generateDown->invoke($generator, $fields, 'add');
    $template = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }
};
PHP);
    $content = $update->invoke($generator, $template, $up, '', '', $down, '', '', '');
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-migration-');
    File::put($path, $content);
    $migration = require $path;
    $value = '12345678901234567890123456789012345.123456789012345678901234567890';

    try {
        $migration->up();
        DB::table($tableName)->insert(['amount' => $value]);

        expect(DB::table($tableName)->value('amount'))->toBe($value);

        $migration->down();

        expect(Schema::hasColumn($tableName, 'amount'))->toBeFalse();
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});

test('generated decimal rollback refuses fractional rows before changing to integer', function () {
    $tableName = 'core_10_lossy_rollback_values';
    Schema::create($tableName, function (Blueprint $table) {
        $table->id();
        $table->integer('amount')->nullable();
    });

    $generator = new CreateDatabaseMigration(app(Filesystem::class));
    $generate = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
    $generateDown = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
    $generatePreflight = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownPreflight');
    $update = new ReflectionMethod(CreateDatabaseMigration::class, 'updateMigrationContent');
    $fields = collect([[
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
    ]]);
    $up = $generate->invoke($generator, $fields, 'update');
    $down = $generateDown->invoke($generator, $fields, 'update');
    $preflight = $generatePreflight->invoke($generator, $fields, 'update', $tableName);
    $template = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }
};
PHP);
    $content = $update->invoke($generator, $template, '', $up, '', '', $down, '', $preflight);
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-rollback-');
    File::put($path, $content);
    $migration = require $path;

    try {
        $migration->up();
        DB::table($tableName)->insert(['amount' => '1.50']);

        expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'lossy')
            ->and(DB::table($tableName)->value('amount'))->toBe('1.50')
            ->and(Schema::getColumnType($tableName, 'amount'))->toBe('text');
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});

test('generated decimal rollback refuses exact integers outside database integer bounds', function () {
    $tableName = 'core_10_out_of_range_rollback_values';
    Schema::create($tableName, function (Blueprint $table) {
        $table->id();
        $table->integer('amount')->nullable();
    });

    $generator = new CreateDatabaseMigration(app(Filesystem::class));
    $generate = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
    $generateDown = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
    $generatePreflight = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownPreflight');
    $update = new ReflectionMethod(CreateDatabaseMigration::class, 'updateMigrationContent');
    $fields = collect([[
        'old' => [
            'slug' => 'amount',
            'type' => Number::class,
            'number_type' => 'integer',
        ],
        'new' => [
            'slug' => 'amount',
            'type' => Number::class,
            'number_type' => 'decimal',
            'precision' => 65,
            'scale' => 2,
        ],
    ]]);
    $up = $generate->invoke($generator, $fields, 'update');
    $down = $generateDown->invoke($generator, $fields, 'update');
    $preflight = $generatePreflight->invoke($generator, $fields, 'update', $tableName);
    $template = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }
};
PHP);
    $content = $update->invoke($generator, $template, '', $up, '', '', $down, '', $preflight);
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-bounds-');
    File::put($path, $content);
    $migration = require $path;

    try {
        $migration->up();
        $value = '1234567890123456789012345678901234567890.00';
        DB::table($tableName)->insert(['amount' => $value]);

        expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'outside integer bounds')
            ->and(DB::table($tableName)->value('amount'))->toBe($value)
            ->and(Schema::getColumnType($tableName, 'amount'))->toBe('text');
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});
