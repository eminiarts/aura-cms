<?php

use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Fields\Number;
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

test('configured number query semantics match field normalization across supported database drivers', function (string $driver) {
    $connectionName = "core_10_configured_decimal_{$driver}";
    $database = $driver === 'pgsql'
        ? getenv('AURA_TEST_POSTGRES_DATABASE')
        : getenv('AURA_TEST_MYSQL_DATABASE');

    if ($driver !== 'sqlite' && ! $database) {
        $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} configured-number query contract.");
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
    $table = 'core10_configured_'.substr(hash('sha256', uniqid((string) getmypid(), true)), 0, 12);
    $number = new Number;
    $decimalField = [
        'slug' => 'amount',
        'number_type' => 'decimal',
        'precision' => 4,
        'scale' => 2,
    ];
    $integerField = [
        'slug' => 'amount',
        'number_type' => 'integer',
        'precision' => 3,
    ];

    $profiles = [
        'decimal' => [
            'field' => $decimalField,
            'target' => '1.23',
            'rows' => [
                ['position' => 1, 'label' => 'negative rounded', 'value' => '-2.345', 'normalized' => '-2.35'],
                ['position' => 2, 'label' => 'negative down', 'value' => '-2.344', 'normalized' => '-2.34'],
                ['position' => 3, 'label' => 'negative zero', 'value' => '-0.004', 'normalized' => '0.00'],
                ['position' => 4, 'label' => 'round down', 'value' => '1.224', 'normalized' => '1.22'],
                ['position' => 5, 'label' => 'canonical', 'value' => '1.23', 'normalized' => '1.23'],
                ['position' => 6, 'label' => 'half up equivalent', 'value' => '1.225', 'normalized' => '1.23'],
                ['position' => 7, 'label' => 'padded equivalent', 'value' => '+001.234', 'normalized' => '1.23'],
                ['position' => 8, 'label' => 'round up', 'value' => '1.235', 'normalized' => '1.24'],
                ['position' => 9, 'label' => 'maximum', 'value' => '99.994', 'normalized' => '99.99'],
                ['position' => 10, 'label' => 'rounding overflow', 'value' => '99.995', 'normalized' => null],
                ['position' => 11, 'label' => 'precision overflow', 'value' => '100', 'normalized' => null],
                ['position' => 12, 'label' => 'over portability scale', 'value' => '0.'.str_repeat('1', 66), 'normalized' => null],
                ['position' => 13, 'label' => 'exponent', 'value' => '1e2', 'normalized' => null],
                ['position' => 14, 'label' => 'injection like', 'value' => '0 OR 1=1', 'normalized' => null],
                ['position' => 15, 'label' => 'empty', 'value' => '', 'normalized' => null],
                ['position' => 16, 'label' => 'null', 'value' => null, 'normalized' => null],
                ['position' => 17, 'label' => 'control padded equivalent', 'value' => "\t+001.234\n", 'normalized' => '1.23'],
                ['position' => 18, 'label' => 'non breaking space', 'value' => "\u{00A0}1.23\u{00A0}", 'normalized' => null],
            ],
        ],
        'integer' => [
            'field' => $integerField,
            'target' => '1',
            'rows' => [
                ['position' => 1, 'label' => 'negative', 'value' => '-2', 'normalized' => '-2'],
                ['position' => 2, 'label' => 'zero', 'value' => '-0', 'normalized' => '0'],
                ['position' => 3, 'label' => 'padded two', 'value' => '+002', 'normalized' => '2'],
                ['position' => 4, 'label' => 'two', 'value' => '2', 'normalized' => '2'],
                ['position' => 5, 'label' => 'fraction', 'value' => '1.5', 'normalized' => null],
                ['position' => 6, 'label' => 'precision overflow', 'value' => '1000', 'normalized' => null],
                ['position' => 7, 'label' => 'exponent', 'value' => '1e2', 'normalized' => null],
                ['position' => 8, 'label' => 'over portability digits', 'value' => str_repeat('9', 66), 'normalized' => null],
            ],
        ],
        'legacy precision' => [
            'field' => [
                'slug' => 'amount',
                'precision' => 3,
            ],
            'target' => '2',
            'rows' => [
                ['position' => 1, 'label' => 'negative', 'value' => '-1', 'normalized' => '-1'],
                ['position' => 2, 'label' => 'padded two', 'value' => '+002', 'normalized' => '2'],
                ['position' => 3, 'label' => 'two', 'value' => '2', 'normalized' => '2'],
                ['position' => 4, 'label' => 'maximum integer', 'value' => '999', 'normalized' => '999'],
                ['position' => 5, 'label' => 'integer precision overflow', 'value' => '1000', 'normalized' => null],
                ['position' => 6, 'label' => 'legacy fraction', 'value' => '1000.5', 'normalized' => '1000.5'],
                ['position' => 7, 'label' => 'over portability scale', 'value' => '0.'.str_repeat('1', 66), 'normalized' => null],
                ['position' => 8, 'label' => 'malformed', 'value' => '2 OR 1=1', 'normalized' => null],
            ],
        ],
        'decimal high scale' => [
            'field' => [
                'slug' => 'amount',
                'number_type' => 'decimal',
                'precision' => 65,
                'scale' => 30,
            ],
            'target' => '2',
            'rows' => [
                [
                    'position' => 1,
                    'label' => 'negative maximum',
                    'value' => '-'.str_repeat('9', 35).'.'.str_repeat('9', 30),
                    'normalized' => '-'.str_repeat('9', 35).'.'.str_repeat('9', 30),
                ],
                [
                    'position' => 2,
                    'label' => 'negative tiny rounded',
                    'value' => '-0.'.str_repeat('0', 29).'15',
                    'normalized' => '-0.'.str_repeat('0', 29).'2',
                ],
                [
                    'position' => 3,
                    'label' => 'negative zero',
                    'value' => '-0.'.str_repeat('0', 30),
                    'normalized' => '0.'.str_repeat('0', 30),
                ],
                [
                    'position' => 4,
                    'label' => 'two',
                    'value' => '2',
                    'normalized' => '2.'.str_repeat('0', 30),
                ],
                [
                    'position' => 5,
                    'label' => 'padded two',
                    'value' => '+0002.'.str_repeat('0', 30),
                    'normalized' => '2.'.str_repeat('0', 30),
                ],
                [
                    'position' => 6,
                    'label' => 'long carry equivalent',
                    'value' => '1.'.str_repeat('9', 29).'95',
                    'normalized' => '2.'.str_repeat('0', 30),
                ],
                [
                    'position' => 7,
                    'label' => 'positive maximum',
                    'value' => str_repeat('9', 35).'.'.str_repeat('9', 30),
                    'normalized' => str_repeat('9', 35).'.'.str_repeat('9', 30),
                ],
                [
                    'position' => 8,
                    'label' => 'rounding overflow',
                    'value' => str_repeat('9', 35).'.'.str_repeat('9', 30).'5',
                    'normalized' => null,
                ],
                [
                    'position' => 9,
                    'label' => 'over portability scale',
                    'value' => '0.'.str_repeat('1', 66),
                    'normalized' => null,
                ],
                [
                    'position' => 10,
                    'label' => 'thousand leading zeros maximum',
                    'value' => str_repeat('0', 1000).str_repeat('9', 35).'.'.str_repeat('9', 30),
                    'normalized' => str_repeat('9', 35).'.'.str_repeat('9', 30),
                ],
            ],
        ],
    ];

    try {
        $connection->getSchemaBuilder()->create($table, function (Blueprint $table): void {
            $table->unsignedInteger('position');
            $table->string('label');
            $table->text('value')->nullable();
        });

        foreach ($profiles as $profile) {
            $connection->table($table)->delete();
            $connection->table($table)->insert(array_map(
                fn (array $row): array => array_intersect_key($row, array_flip(['position', 'label', 'value'])),
                $profile['rows'],
            ));
            $column = $connection->getQueryGrammar()->wrap('value');
            $target = $number->normalizeForStorage($profile['target'], $profile['field'], null, FieldValueStorage::Meta);
            $targetKey = ExactDecimal::sortableKey($target);
            $queryConfiguration = $number->exactQueryConfiguration($profile['field']);
            $numberType = $profile['field']['number_type'] ?? 'legacy';

            foreach (['=', '!=', '>', '<', '>=', '<='] as $operator) {
                $query = $connection->table($table);
                ExactDecimal::applyComparison($query, $connection, $column, $operator, $target, $queryConfiguration);
                $expected = collect($profile['rows'])
                    ->filter(function (array $row) use ($operator, $targetKey): bool {
                        if ($row['normalized'] === null) {
                            return false;
                        }

                        $comparison = strcmp(ExactDecimal::sortableKey($row['normalized']), $targetKey);

                        return match ($operator) {
                            '=' => $comparison === 0,
                            '!=' => $comparison !== 0,
                            '>' => $comparison > 0,
                            '<' => $comparison < 0,
                            '>=' => $comparison >= 0,
                            '<=' => $comparison <= 0,
                        };
                    })
                    ->pluck('label')
                    ->all();

                expect($query->orderBy('position')->pluck('label')->all())
                    ->toBe($expected, "{$driver} configured {$numberType} {$operator} comparison");
            }

            foreach (['asc', 'desc'] as $direction) {
                $query = $connection->table($table);
                ExactDecimal::applySorting($query, $connection, $column, $direction, $queryConfiguration);
                $query->orderBy('position');
                $expected = collect($profile['rows'])
                    ->sort(function (array $left, array $right) use ($direction): int {
                        if ($left['normalized'] === null || $right['normalized'] === null) {
                            return match (true) {
                                $left['normalized'] === null && $right['normalized'] === null => $left['position'] <=> $right['position'],
                                $left['normalized'] === null => 1,
                                default => -1,
                            };
                        }

                        $comparison = strcmp(
                            ExactDecimal::sortableKey($left['normalized']),
                            ExactDecimal::sortableKey($right['normalized']),
                        );

                        return ($direction === 'desc' ? -$comparison : $comparison) ?: ($left['position'] <=> $right['position']);
                    })
                    ->pluck('label')
                    ->values()
                    ->all();

                expect($query->pluck('label')->all())
                    ->toBe($expected, "{$driver} configured {$numberType} {$direction} sorting");
            }
        }
    } finally {
        $connection->getSchemaBuilder()->dropIfExists($table);

        if ($driver !== 'sqlite') {
            DB::purge($connectionName);
        }
    }
})->with(['sqlite', 'mysql', 'pgsql']);
