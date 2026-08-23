<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resourcePath = app_path('Aura/Resources');

    Schema::create('blog_posts', function ($table) {
        $table->id();
        $table->string('title');
        $table->text('body')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    $path = $this->resourcePath.'/BlogPost.php';

    if (File::exists($path)) {
        File::delete($path);
    }

    if (File::isDirectory($this->resourcePath) && empty(File::files($this->resourcePath))) {
        File::deleteDirectory($this->resourcePath);
    }
});

it('generates a syntactically valid resource bound to the source table', function () {
    $this->artisan('aura:transform-table-to-resource', ['table' => 'blog_posts'])
        ->assertExitCode(0);

    $file = $this->resourcePath.'/BlogPost.php';

    expect(File::exists($file))->toBeTrue();

    $lint = Process::fromShellCommandline('php -l '.escapeshellarg($file));
    $lint->run();

    expect($lint->getExitCode())->toBe(0, $lint->getOutput().$lint->getErrorOutput());

    expect(File::get($file))
        ->toContain('use Aura\Base\Resource;')
        ->toContain("public static ?string \$slug = 'blog-post';")
        ->toContain('public static $customTable = true;')
        ->toContain("protected \$table = 'blog_posts';")
        ->toContain("'slug' => 'title',");
});
