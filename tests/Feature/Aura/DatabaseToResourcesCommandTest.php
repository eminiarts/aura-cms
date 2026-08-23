<?php

use Aura\Base\Commands\DatabaseToResources;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

        protected function getAllTables(): array
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
        $result = runDatabaseToResourcesCommandForTest(['articles', 'comments', 'migrations', 'failed_jobs', 'password_resets', 'sessions']);

        expect($result['exitCode'])->toBe(0);
        expect($result['processedTables'])->toHaveCount(2)
            ->toContain('articles', 'comments')
            ->not->toContain('migrations', 'failed_jobs', 'password_resets', 'sessions');
    });

    it('processes all non-system tables', function () {
        $expectedTables = ['articles', 'comments'];
        $result = runDatabaseToResourcesCommandForTest(['articles', 'comments', 'migrations', 'failed_jobs', 'password_resets', 'sessions']);

        expect($result['processedTables'])
            ->toHaveCount(2)
            ->toEqual($expectedTables);
    });

    it('shows success message after completion', function () {
        $result = runDatabaseToResourcesCommandForTest(['articles', 'comments']);

        expect($result['exitCode'])->toBe(0);
        expect($result['output'])->toContain('Resources generated successfully');
    });
});

describe('system tables filtering', function () {
    it('skips Laravel and Aura tables', function () {
        $systemTables = [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'migrations',
            'notifications',
            'password_reset_tokens',
            'password_resets',
            'personal_access_tokens',
            'sessions',
            'aura_migration_ownership',
            'meta',
            'options',
            'permissions',
            'post_relations',
            'posts',
            'roles',
            'teams',
            'user_role',
            'users',
        ];

        $result = runDatabaseToResourcesCommandForTest([...$systemTables, 'articles']);

        expect($result['processedTables'])->toEqual(['articles']);
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
        $result = runDatabaseToResourcesCommandForTest(['migrations', 'failed_jobs', 'password_resets', 'sessions', 'users', 'posts']);

        expect($result['exitCode'])->toBe(0);
        expect($result['processedTables'])->toBeEmpty();
    });
});

it('discovers tables without Doctrine DBAL', function () {
    Schema::create('command_source', function ($table) {
        $table->id();
    });

    $command = new class extends DatabaseToResources
    {
        public function tableNames(): array
        {
            return $this->getAllTables();
        }
    };

    expect($command->tableNames())->toContain('command_source');
});
