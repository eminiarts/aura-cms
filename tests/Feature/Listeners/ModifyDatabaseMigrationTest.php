<?php

use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Events\SaveFields;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->files = Mockery::mock(Filesystem::class);
    $this->listener = new ModifyDatabaseMigration($this->files);
});

afterEach(function () {
    Mockery::close();
});

it('handles the SaveFields event correctly and generates the migration', function () {
    $event = new SaveFields(getNewFields(), getOldFields(), mockModel());

    Artisan::shouldReceive('call')->with('make:migration', Mockery::type('array'))->once();
    File::shouldReceive('exists')->andReturn(false);
    $this->files->shouldReceive('get')->andReturn(getDummyMigrationContent());
    $this->files->shouldReceive('put')->andReturnTrue();
    Artisan::shouldReceive('call')->with('aura:schema-update', Mockery::type('array'))->once();

    $this->listener->handle($event);

    $this->assertTrue(true);
});

it('handles migration already exists case', function () {
    $event = new SaveFields( getNewFields(), getOldFields(),mockModel());

    Artisan::shouldReceive('call')->never();
    File::shouldReceive('exists')->andReturn(true);
    $this->files->shouldReceive('get')->andReturn(getDummyMigrationContent());
    $this->files->shouldReceive('put')->andReturnTrue();
    Artisan::shouldReceive('call')->with('aura:schema-update', Mockery::type('array'))->once();

    $this->listener->handle($event);

    $this->assertTrue(true);
});

it('generates the correct schema', function () {
    $fields = collect([
        ['slug' => 'name', 'type' => 'string'],
        ['slug' => 'email', 'type' => 'string'],
    ]);

    $reflection = new ReflectionClass($this->listener);
    $method = $reflection->getMethod('generateSchema');
    $method->setAccessible(true);

    $schema = $method->invoke($this->listener, $fields);

    expect($schema)->toContain('$table->id();');
    expect($schema)->toContain("\$table->string('name')->nullable();");
    expect($schema)->toContain("\$table->string('email')->nullable();");
    expect($schema)->toContain('$table->foreignId("user_id");');
    expect($schema)->toContain('$table->foreignId("team_id");');
    expect($schema)->toContain('$table->timestamps();');
    expect($schema)->toContain('$table->softDeletes();');
});

// Helper functions
function mockModel()
{
    $model = Mockery::mock();
    $model->shouldReceive('getTable')->andReturn('test_table');
    return $model;
}

function getNewFields()
{
    return [
        ['slug' => 'name', 'type' => 'string'],
        ['slug' => 'email', 'type' => 'string'],
    ];
}

function getOldFields()
{
    return [
        ['slug' => 'old_name', 'type' => 'string'],
        ['slug' => 'old_email', 'type' => 'string'],
    ];
}

function getDummyMigrationContent()
{
    return <<<'EOD'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('test_table', function (Blueprint $table) {
            $table->id();
            // Add fields here
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('test_table');
    }
};
EOD;
}