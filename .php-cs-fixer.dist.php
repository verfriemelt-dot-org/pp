<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src'])
    ->in([__DIR__ . '/tests'])
;

$config = new PhpCsFixer\Config();
$config->setRules([
    '@Symfony' => true,
    '@PER' => true,

    'php_unit_test_case_static_method_calls' => true,
    'declare_strict_types' => true,
    'php_unit_strict' => true,

    // overwrite some symfony defaults
    'blank_line_before_statement' => false,
    'self_accessor' => false,

    // away you go
    'yoda_style' => false,

    // multiline dotting
    'phpdoc_annotation_without_dot' => true,
    'phpdoc_summary' => false,
    'phpdoc_to_comment' => false,

    // we have long params list on throw
    'single_line_throw' => false,

    'php_unit_method_casing' => ["case" => 'snake_case'],

    // wtf, we need space
    'concat_space' => ['spacing' => 'one'],
    'global_namespace_import' => ['import_classes' => true, 'import_constants' => false, 'import_functions' => false],

    'phpdoc_line_span' => [
        'property' => 'single',
        'const' => 'single',
        'method' => 'multi'
    ]
]);
$config->setFinder($finder);
$config->setRiskyAllowed(true);
$config->setUsingCache(false);


return $config;
