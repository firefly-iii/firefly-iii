<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
                   ->withPaths([
                                   __DIR__ . '/../app',
                                   __DIR__ . '/../bootstrap',
                                   __DIR__ . '/../config',
                                   __DIR__ . '/../public',
                                   __DIR__ . '/../resources',
                                   __DIR__ . '/../routes',
                                   __DIR__ . '/../tests',
                               ])
    // uncomment to reach your current PHP version
                   ->withPhpSets()
                   ->withTypeCoverageLevel(0)
                   ->withDeadCodeLevel(0)
                   ->withCodeQualityLevel(0);
