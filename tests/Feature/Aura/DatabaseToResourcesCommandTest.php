<?php

use Aura\Base\Commands\DatabaseToResources;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Tester\CommandTester;

uses(RefreshDatabase::class);

function runDatabaseToResourcesCommandForTest(array $tables): array
{
    $command = new class($tables) extends DatabaseToResources
    {
        public array $processedTables = [];

        public function __construct(private array $tables)
        {
            parent::__construct();
        }

        protected function getAllTables()
        {
            return $this->tables;
        }

        protected function transformTable(string $table): int
        {
            $this->processedTables[] = $table;

            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());

    $tester = new CommandTester($command);
    $exitCode = $tester->execute([]);

    return [
        'exitCode' => $exitCode,
        'output' => $tester->getDisplay(),
        'processedTables' => $command->processedTables,
    ];
}

describe('resource generation', function () {
    it('executes database to resources command successfully', function () {
        $result = runDatabaseToResourcesCommandForTest(['users', 'posts', 'comments', 'migrations', 'failed_jobs', 'password_resets', 'settions']);

        expect($result['exitCode'])->toBe(0);
        expect($result['processedTables'])->toHaveCount(3)
            ->toContain('users', 'posts', 'comments')
            ->not->toContain('migrations', 'failed_jobs', 'password_resets', 'settions');
    });

    it('processes all non-system tables', function () {
        $expectedTables = ['users', 'posts', 'comments'];
        $result = runDatabaseToResourcesCommandForTest(['users', 'posts', 'comments', 'migrations', 'failed_jobs', 'password_resets', 'settions']);

        expect($result['processedTables'])
            ->toHaveCount(3)
            ->toEqual($expectedTables);
    });

    it('shows success message after completion', function () {
        $result = runDatabaseToResourcesCommandForTest(['users', 'posts', 'comments']);

        expect($result['exitCode'])->toBe(0);
        expect($result['output'])->toContain('Resources generated successfully');
    });
});

describe('system tables filtering', function () {
    it('skips system tables', function () {
        $result = runDatabaseToResourcesCommandForTest(['users', 'posts', 'comments', 'migrations', 'failed_jobs', 'password_resets', 'settions']);

        $systemTables = ['migrations', 'failed_jobs', 'password_resets', 'settions'];
        foreach ($systemTables as $table) {
            expect($result['processedTables'])->not->toContain($table);
        }
    });
});

describe('edge cases', function () {
    it('handles empty database gracefully', function () {
        $result = runDatabaseToResourcesCommandForTest([]);

        expect($result['exitCode'])->toBe(0);
        expect($result['output'])->toContain('Resources generated successfully');
        expect($result['processedTables'])->toBeEmpty();
    });

    it('handles database with only system tables', function () {
        $result = runDatabaseToResourcesCommandForTest(['migrations', 'failed_jobs', 'password_resets', 'settions']);

        expect($result['exitCode'])->toBe(0);
        expect($result['processedTables'])->toBeEmpty();
    });
});
