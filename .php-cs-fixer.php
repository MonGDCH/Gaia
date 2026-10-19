<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PHP-CS-Fixer 配置
|--------------------------------------------------------------------------
| Gaia 项目代码风格自动修复工具配置
| 使用：php-cs-fixer fix
|
*/

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/app')
    ->in(__DIR__ . '/support')
    ->in(__DIR__ . '/config')
    ->exclude('vendor')
    ->exclude('runtime')
    ->exclude('bin')
    ->name('*.php')
    ->ignoreDotFiles(true);

return (new PhpCsFixer\Config())
    ->setRules([
        // PSR-12 全套
        '@PSR12' => true,
        '@PSR12:risky' => true,

        // 强制 declare(strict_types=1)
        'declare_strict_types' => true,

        // 行长度限制
        'line_length' => [
            'limit' => 120,
            'soft_limit' => 80,
            'allow_long_tail' => true,
        ],

        // import 排序
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'single_import_per_statement' => true,

        // 数组格式
        'array_syntax' => ['syntax' => 'short'],

        // 空白
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_before_comma_in_array' => true,
        'whitespace_after_comma_in_array' => true,

        // 注释
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'phpdoc_trim' => true,

        // 控制结构
        'no_else_after_return' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,

        // 字符串
        'single_quote' => true,
        'no_binary_string' => true,

        // 命名（警告级别，不自动修复）
        'no_underscore_before_method' => false,  // Gaia 方法名保留 camelCase，不强制
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
