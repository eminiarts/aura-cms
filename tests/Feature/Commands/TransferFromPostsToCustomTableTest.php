<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Create test data in posts table
    DB::table('posts')->insert([
        'id' => 1,
        'type' => 'test-resource',
        'user_id' => 1,
        'team_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

afterEach(function () {
    // Clean up test data
    DB::table('posts')->where('type', 'test-resource')->delete();
    DB::statement('DROP TABLE IF EXISTS test_resources');
});

it('can transfer data from posts to custom table', function () {
    // Create the test_resources table temporarily
    DB::statement('CREATE TABLE IF NOT EXISTS test_resources (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        team_id INTEGER,
        test_field TEXT,
        created_at TIMESTAMP,
        updated_at TIMESTAMP
    )');

    // Create a mock resource class for testing
    $mockResourceClass = new class
    {
        public $name = 'Test Resource';

        public function create($data)
        {
            return DB::table('test_resources')->insert($data);
        }

        public function getType()
        {
            return 'test-resource';
        }
    };

    // Insert meta data after getting the actual class name
    DB::table('meta')->insert([
        'metable_type' => get_class($mockResourceClass),
        'metable_id' => 1,
        'key' => 'test_field',
        'value' => 'test_value',
    ]);

    $this->artisan('aura:transfer-from-posts-to-custom-table', [
        'resource' => get_class($mockResourceClass),
    ])->assertExitCode(0);

    // Verify that data was transferred correctly
    $this->assertDatabaseHas('test_resources', [
        'user_id' => 1,
        'team_id' => 1,
        'test_field' => 'test_value',
    ]);
});
