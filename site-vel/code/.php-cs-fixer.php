<?php

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'fully_qualified_strict_types' => [
            'phpdoc_tags' => [
                'param', 'phpstan-param', 'phpstan-property', 'phpstan-property-read', 'phpstan-property-write',
                'phpstan-return', 'phpstan-var', 'property', 'property-read', 'property-write', 'psalm-param',
                'psalm-property', 'psalm-property-read', 'psalm-property-write', 'psalm-return', 'psalm-var', 'return',
                'see', 'var']
            ,
        ],
        'phpdoc_to_comment' => false,
    ])
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setFinder($finder);
