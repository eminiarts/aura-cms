<?php

use Eminiarts\Aura\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

uses()->group('fields')->in('Feature/Fields');

uses()->group('flows')->in('Feature/Flows');

uses()->group('table')->in('Feature/Table');
