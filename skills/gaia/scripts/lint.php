<?php
/**
 * Gaia Project Linter
 *
 * 自动检查 Gaia 项目是否符合开发规范
 * 使用：php skills/gaia/scripts/lint.php
 *
 * 检查项：
 * - declare(strict_types=1) 是否存在
 * - 命名空间与目录是否匹配（app/ 目录下）
 * - Service 类是否使用 Instance trait
 * - Process 类 $processConfig 是否存在、是否误加类型、listen 是否为空
 * - Command/Process 的 $defaultName 等静态属性是否误加类型（父类均无类型）
 * - Process 是否误定义 onWorkerStop 方法（框架启动时会立即调用同名方法）
 * - Process 是否已在 AppCommand 注册
 * - 是否修改 $GLOBALS（常驻内存红线）
 */

declare(strict_types=1);

$errors = 0;
$warnings = 0;

/**
 * 定位项目根目录
 *
 * 兼容两种技能安装位置：
 * - <项目>/skills/gaia/scripts/lint.php
 * - <项目>/.agents/skills/gaia/scripts/lint.php
 *
 * 判定依据：同时存在 composer.json、app 目录与 gaia 启动文件的目录
 *
 * @param string $start
 * @return string
 */
function detectRootPath(string $start): string
{
    $dir = $start;
    for ($i = 0; $i < 8; $i++) {
        if (is_file($dir . DIRECTORY_SEPARATOR . 'composer.json')
            && is_dir($dir . DIRECTORY_SEPARATOR . 'app')
            && is_file($dir . DIRECTORY_SEPARATOR . 'gaia')) {
            return $dir;
        }
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }

    return dirname(__DIR__, 4);
}

$rootPath = detectRootPath(__DIR__);
echo 'Project root: ' . $rootPath . PHP_EOL . PHP_EOL;

/**
 * 递归遍历指定目录下所有 PHP 文件
 *
 * @param string $dir
 * @return \Generator<SplFileInfo>
 */
function iteratePhpFiles(string $dir): \Generator
{
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            yield $file;
        }
    }
}

echo "=== Gaia Project Linter ===" . PHP_EOL . PHP_EOL;

// ---------- 1. declare(strict_types=1) + 命名空间与目录匹配 ----------
echo "[1/6] Checking declare(strict_types=1) and namespace..." . PHP_EOL;
$requiredDirs = ['app', 'support'];
foreach ($requiredDirs as $dir) {
    $fullDir = $rootPath . DIRECTORY_SEPARATOR . $dir;
    foreach (iteratePhpFiles($fullDir) as $file) {
        $relPath = str_replace($rootPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $content = file_get_contents($file->getPathname());

        if (strpos($content, 'declare(strict_types=1)') === false) {
            echo "  ❌ MISSING declare: $relPath" . PHP_EOL;
            $errors++;
        }

        // 命名空间与目录匹配检查（app/ 目录下，仅针对含类/接口/trait 声明的文件；helpers.php 等纯函数文件跳过）
        if ($dir === 'app' && preg_match('/^\s*(abstract\s+|final\s+)?(class|interface|trait)\s+\w+/m', $content)) {
            $rel = substr($file->getPathname(), strlen($fullDir) + 1);
            $subDir = dirname($rel);
            $expectedNs = $subDir === '.'
                ? 'app'
                : 'app\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $subDir);
            if (preg_match('/^\s*namespace\s+([^;\s]+)\s*;/m', $content, $m)) {
                if ($m[1] !== $expectedNs) {
                    echo "  ❌ Namespace mismatch: $relPath (expect `$expectedNs`, got `{$m[1]}`)" . PHP_EOL;
                    $errors++;
                }
            } else {
                echo "  ⚠️  No namespace found: $relPath" . PHP_EOL;
                $warnings++;
            }
        }
    }
}
echo "  Done." . PHP_EOL . PHP_EOL;

// ---------- 2. Service 类 ----------
echo "[2/6] Checking Service classes..." . PHP_EOL;
$serviceDir = $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'service';
if (is_dir($serviceDir)) {
    foreach (glob($serviceDir . DIRECTORY_SEPARATOR . '*Service.php') as $file) {
        $name = basename($file);
        $content = file_get_contents($file);
        if (strpos($content, 'use mon\\util\\Instance') === false && strpos($content, 'use Instance') === false) {
            echo "  ❌ Missing Instance trait: app/service/$name" . PHP_EOL;
            $errors++;
        } else {
            echo "  ✅ $name" . PHP_EOL;
        }
    }
}
echo PHP_EOL;

// ---------- 3. Process 类 $processConfig ----------
echo "[3/6] Checking Process processConfig..." . PHP_EOL;
$processDir = $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'process';
if (is_dir($processDir)) {
    foreach (glob($processDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
        $name = basename($file);
        $content = file_get_contents($file);

        // 类型化属性检测（父类 gaia\Process 无类型，加类型会 Fatal Error）
        if (preg_match('/static\s+(array|string|int|bool|float)\s+\$processConfig\b/', $content)) {
            echo "  ❌ Typed \$processConfig (parent gaia\\Process is untyped, Fatal Error): app/process/$name" . PHP_EOL;
            $errors++;
            continue;
        }

        if (!preg_match('/static\s+\$processConfig\s*=/', $content)) {
            echo "  ⚠️  No \$processConfig defined: app/process/$name" . PHP_EOL;
            $warnings++;
            continue;
        }

        // 空 listen 检测（兼容脚手架的对齐空格写法 'listen'        => ''）
        if (preg_match("/'listen'\s*=>\s*''/", $content)) {
            echo "  ⚠️  Empty listen config: app/process/$name (需填写，内部进程可用 tcp://0.0.0.0:0)" . PHP_EOL;
            $warnings++;
        } else {
            echo "  ✅ $name" . PHP_EOL;
        }
    }
}
echo PHP_EOL;

// ---------- 4. Process 是否在 AppCommand 注册 ----------
echo "[4/6] Checking Process registration in AppCommand..." . PHP_EOL;
$appCmdFile = $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'command' . DIRECTORY_SEPARATOR . 'AppCommand.php';
if (is_file($appCmdFile)) {
    $appCmdContent = file_get_contents($appCmdFile);
    preg_match("/protected\s+\\\$process\s*=\s*\[([^\]]*)\]/s", $appCmdContent, $registeredMatches);
    $registered = $registeredMatches[1] ?? '';

    if (is_dir($processDir)) {
        foreach (glob($processDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
            $name = basename($file, '.php');
            if (strpos($registered, "\\\\{$name}::class") === false && strpos($registered, "{$name}::class") === false) {
                echo "  ⚠️  Not registered: app/process/$name.php (add to AppCommand::\$process)" . PHP_EOL;
                $warnings++;
            } else {
                echo "  ✅ $name" . PHP_EOL;
            }
        }
    }
} else {
    echo "  ⚠️  AppCommand.php not found, skip check." . PHP_EOL;
}
echo PHP_EOL;

// ---------- 5. Workerman 常驻内存红线 ----------
echo "[5/6] Checking resident-memory red lines..." . PHP_EOL;
foreach (iteratePhpFiles($rootPath . DIRECTORY_SEPARATOR . 'app') as $file) {
    $relPath = str_replace($rootPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $content = file_get_contents($file->getPathname());

    // 框架陷阱：禁止定义 onWorkerStop 方法（启动时会被框架同步调用）
    if (strpos($relPath, 'process' . DIRECTORY_SEPARATOR) !== false
        && preg_match('/function\s+onWorkerStop\s*\(/', $content)) {
        echo "  ❌ Defines onWorkerStop() method (framework calls it at STARTUP, use \$worker->onWorkerStop closure instead): $relPath" . PHP_EOL;
        $errors++;
    }

    // 父类 mon\console\Command 的 $default* 属性均无类型，子类加类型会 Fatal Error
    if (preg_match('/static\s+(string|int|bool|array)\s+\$default(Name|Description|Group)\b/', $content)) {
        echo "  ❌ Typed \$default* property (parent mon\\console\\Command is untyped, Fatal Error): $relPath" . PHP_EOL;
        $errors++;
    }

    // Timer 泄漏风险：有 Timer::add 但全文件无 Timer::del
    if (strpos($relPath, 'process' . DIRECTORY_SEPARATOR) !== false) {
        $hasTimerAdd = strpos($content, 'Timer::add') !== false;
        $hasTimerDel = strpos($content, 'Timer::del') !== false;
        if ($hasTimerAdd && !$hasTimerDel) {
            echo "  ⚠️  Timer::add without any Timer::del (leak risk if repeatedly added): $relPath" . PHP_EOL;
            $warnings++;
        }
    }
}

// 检查全局变量修改（app/ 与 support/ 目录）
$forbiddenPatterns = [
    '/\$GLOBALS\s*\[/' => 'GLOBALS 修改',
];
foreach ($requiredDirs as $gDir) {
    foreach (iteratePhpFiles($rootPath . DIRECTORY_SEPARATOR . $gDir) as $file) {
        // 跳过 bootstrap.php（正常 define 常量）
        if (strpos($file->getFilename(), 'bootstrap') !== false) continue;
        $content = file_get_contents($file->getPathname());
        foreach ($forbiddenPatterns as $pattern => $label) {
            if (preg_match($pattern, $content)) {
                echo "  ❌ $label: " . str_replace($rootPath . DIRECTORY_SEPARATOR, '', $file->getPathname()) . PHP_EOL;
                $errors++;
            }
        }
    }
}
echo PHP_EOL;

// ---------- 6. Summary ----------
echo "=== Summary ===" . PHP_EOL;
echo "Errors:   $errors" . PHP_EOL;
echo "Warnings: $warnings" . PHP_EOL;
echo "Status:   " . ($errors > 0 ? 'FAIL ❌' : 'PASS ✅') . PHP_EOL;

exit($errors > 0 ? 1 : 0);
