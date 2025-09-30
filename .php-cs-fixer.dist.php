<?php

$paths = [__DIR__ . '/src', __DIR__ . '/tests'];
$paths = array_values(array_filter($paths, static fn(string $p) => is_dir($p)));

$finder = (new PhpCsFixer\Finder())
    ->in($paths)
    ->exclude(['vendor']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_before_statement' => ['statements' => ['return']],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => false,
        'fully_qualified_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_no_empty_return' => false,
        'phpdoc_order' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
    ]);
