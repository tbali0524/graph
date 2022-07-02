<?php

// php-cs-fixer configuration
//   rulesets: https://cs.symfony.com/doc/ruleSets/index.html

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in('src/')
    ->name('*.php')
    ->ignoreVCSIgnored(true)
    ->exclude('.git/')
    ->exclude('.tools/')
    ->exclude('.vscode/')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP81Migration' => true,
        '@PHP80Migration:risky' => true,
        '@Symfony' => true,

        // override some @Symfony rules
        'binary_operator_spaces' => ['operators' => ['=' => null, '=>' => null]],
        'blank_line_before_statement' => false,
        'class_attributes_separation' => false,
        'concat_space' => ['spacing' => 'one'],
        'increment_style'=> ['style' => 'post'],
        'yoda_style' => false,
    ])
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__ . '/.tools/.php-cs-fixer.cache')
    ->setIndent("    ")
    ->setLineEnding("\n")
    ->setFinder($finder)
;
