<?php

use Aura\Base\Support\ExactDecimal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

test('exact decimal query semantics are portable across supported database drivers', function (string $driver) {
    $connectionName = "core_10_decimal_{$driver}";
    $database = $driver === 'pgsql'
        ? getenv('AURA_TEST_POSTGRES_DATABASE')
        : getenv('AURA_TEST_MYSQL_DATABASE');

    if ($driver !== 'sqlite' && ! $database) {
        $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} decimal query contract.");
    }

    if ($driver === 'sqlite') {
        $connectionName = (string) config('database.default');
    } else {
        $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
        $configuration = [
            'driver' => $driver,
            'host' => getenv("AURA_TEST_{$prefix}_HOST") ?: '127.0.0.1',
            'port' => getenv("AURA_TEST_{$prefix}_PORT") ?: ($driver === 'mysql' ? '3306' : '5432'),
            'database' => $database,
            'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: ($driver === 'mysql' ? 'root' : getenv('USER')),
            'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
            'prefix' => '',
        ];
        $configuration += $driver === 'mysql'
            ? ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true]
            : ['search_path' => 'public'];
        config()->set("database.connections.{$connectionName}", $configuration);
        DB::purge($connectionName);
    }

    $connection = DB::connection($connectionName);
    $table = 'core10_decimal_'.substr(hash('sha256', uniqid((string) getmypid(), true)), 0, 12);
    $rows = [
        ['position' => 1, 'label' => 'negative huge', 'value' => '-'.str_repeat('9', 65)],
        ['position' => 2, 'label' => 'negative ten', 'value' => '-10'],
        ['position' => 3, 'label' => 'negative two', 'value' => '-2.00'],
        ['position' => 4, 'label' => 'negative high scale', 'value' => '-0.'.str_repeat('0', 64).'1'],
        ['position' => 5, 'label' => 'negative zero', 'value' => '-0'],
        ['position' => 6, 'label' => 'zero', 'value' => '0.000'],
        ['position' => 7, 'label' => 'two padded', 'value' => '+0002.0000'],
        ['position' => 8, 'label' => 'two', 'value' => '2'],
        ['position' => 9, 'label' => 'space padded two', 'value' => '  +0002.0000  '],
        ['position' => 10, 'label' => 'tab padded two', 'value' => "\t2\t"],
        ['position' => 11, 'label' => 'newline padded two', 'value' => "2\n"],
        ['position' => 12, 'label' => 'ten', 'value' => '10'],
        ['position' => 13, 'label' => 'positive huge', 'value' => str_repeat('9', 65)],
        ['position' => 14, 'label' => 'over precision', 'value' => str_repeat('9', 66)],
        ['position' => 15, 'label' => 'over scale', 'value' => '0.'.str_repeat('1', 66)],
        ['position' => 16, 'label' => 'exponent', 'value' => '1e3'],
        ['position' => 17, 'label' => 'partial decimal', 'value' => '1.'],
        ['position' => 18, 'label' => 'internal whitespace', 'value' => "2\t0"],
        ['position' => 19, 'label' => 'injection-like', 'value' => '0 OR 1=1'],
        ['position' => 20, 'label' => 'empty', 'value' => ''],
        ['position' => 21, 'label' => 'null', 'value' => null],
    ];

    try {
        $connection->getSchemaBuilder()->create($table, function (Blueprint $table): void {
            $table->unsignedInteger('position');
            $table->string('label');
            $table->text('value')->nullable();
        });
        $connection->table($table)->insert($rows);
        $column = $connection->getQueryGrammar()->wrap('value');

        $ascending = $connection->table($table);
        ExactDecimal::applySorting($ascending, $connection, $column, 'asc');
        $ascending->orderBy('position');

        $descending = $connection->table($table);
        ExactDecimal::applySorting($descending, $connection, $column, 'desc');
        $descending->orderBy('position');

        expect($ascending->pluck('label')->all())->toBe(array_column($rows, 'label'))
            ->and($descending->pluck('label')->all())->toBe([
                'positive huge',
                'ten',
                'two padded',
                'two',
                'space padded two',
                'tab padded two',
                'newline padded two',
                'negative zero',
                'zero',
                'negative high scale',
                'negative two',
                'negative ten',
                'negative huge',
                'over precision',
                'over scale',
                'exponent',
                'partial decimal',
                'internal whitespace',
                'injection-like',
                'empty',
                'null',
            ]);

        $expectedFilters = [
            '=' => ['two padded', 'two', 'space padded two', 'tab padded two', 'newline padded two'],
            '!=' => ['negative huge', 'negative ten', 'negative two', 'negative high scale', 'negative zero', 'zero', 'ten', 'positive huge'],
            '>' => ['ten', 'positive huge'],
            '<' => ['negative huge', 'negative ten', 'negative two', 'negative high scale', 'negative zero', 'zero'],
            '>=' => ['two padded', 'two', 'space padded two', 'tab padded two', 'newline padded two', 'ten', 'positive huge'],
            '<=' => ['negative huge', 'negative ten', 'negative two', 'negative high scale', 'negative zero', 'zero', 'two padded', 'two', 'space padded two', 'tab padded two', 'newline padded two'],
        ];

        foreach ($expectedFilters as $operator => $expected) {
            $query = $connection->table($table);
            ExactDecimal::applyComparison($query, $connection, $column, $operator, '2');

            expect($query->orderBy('position')->pluck('label')->all())->toBe($expected, "{$driver} {$operator} comparison");
        }
    } finally {
        $connection->getSchemaBuilder()->dropIfExists($table);

        if ($driver !== 'sqlite') {
            DB::purge($connectionName);
        }
    }
})->with(['sqlite', 'mysql', 'pgsql']);
