<?php


/*
 * rector.php
 * Copyright (c) 2025 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\Transform\Rector\String_\StringToClassConstantRector;
use RectorLaravel\Set\LaravelLevelSetList;


return RectorConfig::configure()
    ->withSkip([
        ChangeOrIfContinueToMultiContinueRector::class,
        StringToClassConstantRector::class => [
            __DIR__ . '/../app/Http/Controllers/Auth/LoginController.php',
        ],
        __DIR__.'/../bootstrap/cache/*'
    ])
    ->withPaths([
        __DIR__ . '/../app',
        __DIR__ . '/../bootstrap',
        __DIR__ . '/../config',
        __DIR__ . '/../public',
        __DIR__ . '/../resources/lang/en_US',
        __DIR__ . '/../routes',
        __DIR__ . '/../tests',
    ])
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_120,
    ])
//    ->withConfiguredRule(ReplaceServiceContainerCallArgRector::class, [
//        new ReplaceServiceContainerCallArg('log', new ClassConstFetch(new Name('Illuminate\Support\Facades\Log'), 'class')),
//    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withPreparedSets(
        codingStyle: false, // leave false
        privatization: false, // leave false.
        naming: false, // leave false
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true,
        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: true

    )
    ->withComposerBased(
        twig: true,
        doctrine: true,
        phpunit: true,
        symfony: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withImportNames(removeUnusedImports: true);// import statements instead of full classes.
