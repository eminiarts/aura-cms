<?php

use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Illuminate\Support\Facades\Schema;

it('installs the durable embedded resource incarnation store idempotently', function () {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    $migration->up();
    $migration->up();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
        ->and(Schema::hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
            'resource_type',
            'resource_key_hash',
            'incarnation',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});
