<?php

namespace Aura\Base\Schema;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class AtomicSchemaUpdate
{
    public static function table(string $table, Closure $callback): void
    {
        $mutate = static fn () => Schema::table($table, $callback);
        $queries = DB::connection()->pretend($mutate);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            self::runMysqlStatements($queries);

            return;
        }

        DB::connection()->transaction($mutate);
    }

    /**
     * @param  array<int, array{query: string, bindings: array<int, mixed>, time: float|null}>  $queries
     */
    private static function runMysqlStatements(array $queries): void
    {
        foreach ($queries as $query) {
            if ($query['bindings'] !== []) {
                throw new RuntimeException('Unable to compile bound MySQL schema statements into one atomic ALTER TABLE statement.');
            }
        }

        $statements = array_values(array_map(
            static fn (array $query): string => rtrim(trim($query['query']), ';'),
            $queries,
        ));

        if ($statements === []) {
            return;
        }

        if (count($statements) === 1) {
            DB::statement($statements[0]);

            return;
        }

        if (preg_match('/^(alter\s+table\s+.+?\s)(.+)$/is', $statements[0], $matches) !== 1) {
            throw new RuntimeException('Unable to compile the MySQL schema update as one atomic ALTER TABLE statement.');
        }

        $prefix = $matches[1];
        $clauses = [$matches[2]];

        foreach (array_slice($statements, 1) as $statement) {
            if (! str_starts_with(strtolower($statement), strtolower($prefix))) {
                throw new RuntimeException('Unable to compile the MySQL schema update as one atomic ALTER TABLE statement.');
            }

            $clauses[] = substr($statement, strlen($prefix));
        }

        DB::statement($prefix.implode(', ', $clauses));
    }
}
