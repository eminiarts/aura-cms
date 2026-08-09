<?php

use Aura\Base\Commands\AuraLayoutCommand;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    $this->layoutTestPath = storage_path('framework/testing/aura-layout-command');
    $this->destinationPath = $this->layoutTestPath.'/resources/views/vendor/aura/components/layout/app.blade.php';

    File::deleteDirectory($this->layoutTestPath);
});

afterEach(function () {
    File::deleteDirectory($this->layoutTestPath);
});

test('layout is published from the package source in a path repository', function () {
    $command = new class($this->destinationPath) extends AuraLayoutCommand
    {
        public function __construct(private readonly string $testDestinationPath)
        {
            parent::__construct();
        }

        public function resolvedSourcePath(): string
        {
            return $this->sourcePath();
        }

        protected function destinationPath(): string
        {
            return $this->testDestinationPath;
        }
    };

    $command->setLaravel($this->app);
    $tester = new CommandTester($command);
    $sourcePath = dirname(__DIR__, 3).'/resources/views/components/layout/app.blade.php';

    expect($command->resolvedSourcePath())->toBe($sourcePath)
        ->and($tester->execute([]))->toBe(0)
        ->and(File::exists($this->destinationPath))->toBeTrue()
        ->and(File::get($this->destinationPath))->toBe(File::get($sourcePath))
        ->and($tester->getDisplay())->toContain('Aura layout file copied successfully.')
        ->and($tester->getDisplay())->toContain($this->destinationPath);
});

test('layout publishing fails clearly when the package source is missing', function () {
    $missingSourcePath = $this->layoutTestPath.'/missing-package/resources/views/components/layout/app.blade.php';

    $command = new class($missingSourcePath, $this->destinationPath) extends AuraLayoutCommand
    {
        public function __construct(
            private readonly string $testSourcePath,
            private readonly string $testDestinationPath,
        ) {
            parent::__construct();
        }

        protected function destinationPath(): string
        {
            return $this->testDestinationPath;
        }

        protected function sourcePath(): string
        {
            return $this->testSourcePath;
        }
    };

    $command->setLaravel($this->app);
    $tester = new CommandTester($command);

    expect($tester->execute([]))->toBe(1)
        ->and($tester->getDisplay())->toContain($missingSourcePath)
        ->and(File::exists($this->destinationPath))->toBeFalse();
});
