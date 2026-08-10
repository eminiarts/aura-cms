<?php

use Aura\Base\Resource;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeamsOffPrivilegedResource extends Resource
{
    public static $customTable = true;

    public static bool $usesMeta = false;

    protected $fillable = ['name', 'user_id'];

    protected $table = 'teams_off_privileged_resources';
}

beforeEach(function (): void {
    if (config('aura.teams')) {
        $this->markTestSkipped('This contract exercises teams-disabled privileged persistence.');
    }

    Schema::create('teams_off_privileged_resources', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->foreignId('user_id')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('teams_off_privileged_resources');
});

it('protects caller transactions from connection callbacks when teams are disabled', function (string $callbackType): void {
    $connection = DB::connection();
    $baselineTransactionLevel = $connection->transactionLevel();
    $beforeExecutingProperty = new ReflectionProperty(Connection::class, 'beforeExecutingCallbacks');
    $beforeStartingTransactionProperty = new ReflectionProperty(Connection::class, 'beforeStartingTransaction');
    $originalBeforeExecutingCallbacks = $beforeExecutingProperty->getValue($connection);
    $originalBeforeStartingTransactionCallbacks = $beforeStartingTransactionProperty->getValue($connection);
    $armed = true;

    $connection->beginTransaction();
    $connection->table('teams_off_privileged_resources')->insert([
        'name' => 'Caller row before rejection',
    ]);
    $attack = function () use ($connection, &$armed): void {
        if (! $armed) {
            return;
        }

        $armed = false;
        $connection->rollBack(0);
    };

    if ($callbackType === 'before-executing') {
        $connection->beforeExecuting($attack);
    } else {
        $connection->beforeStartingTransaction($attack);
    }

    try {
        expect(fn () => TeamsOffPrivilegedResource::createForOwnerForSystem(73, [
            'name' => 'Rejected privileged row',
        ], $connection))->toThrow(LogicException::class, 'transaction state');

        expect($armed)->toBeTrue();
        $armed = false;

        expect($connection->transactionLevel())->toBe($baselineTransactionLevel + 1)
            ->and($connection->table('teams_off_privileged_resources')->pluck('name')->all())
            ->toBe(['Caller row before rejection']);

        $connection->table('teams_off_privileged_resources')->insert([
            'name' => 'Caller row after rejection',
        ]);
        $connection->rollBack($baselineTransactionLevel);

        expect($connection->transactionLevel())->toBe($baselineTransactionLevel)
            ->and($connection->table('teams_off_privileged_resources')->count())->toBe(0);
    } finally {
        $armed = false;
        $beforeExecutingProperty->setValue($connection, $originalBeforeExecutingCallbacks);
        $beforeStartingTransactionProperty->setValue($connection, $originalBeforeStartingTransactionCallbacks);

        if ($connection->transactionLevel() > $baselineTransactionLevel) {
            $connection->rollBack($baselineTransactionLevel);
        }
    }
})->with(['before-executing', 'before-starting-transaction']);
