<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/docs',
        __DIR__ . '/public',
        __DIR__ . '/sentience',
        __DIR__ . '/src',
        __DIR__ . '/*.php'
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php83: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withRules([
        DeclareStrictTypesRector::class
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class
    ]);
;
;
