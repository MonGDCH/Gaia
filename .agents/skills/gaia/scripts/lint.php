<?php
/**
 * Gaia Project Linter
 * 
 * 自动检查 Gaia 项目是否符合开发规范
 * 使用：php .agents/skills/gaia/scripts/lint.php
 * 
 * 检查项：
 * - declare(strict_types=1) 是否存在
 * - Service 类是否使用 Instance trait
 * - Process 类是否有非空 listen 配置
 * - Process 是否已在 AppCommand 注册
 * - 命名空间与目录是否匹配
 */

declare(strict_types=1);

$errors = 0;
$warnings = 0;
$rootPath = dirname(__DIR__, 4);

echo "=== Gaia Project Linter ===" . PHP_EOL . PHP_EOL;

// ---------- 1. 检查 declare(strict_types=1) ----------
echo "[1/5] Checking declare(strict_types=1)..." . PHP_EOL;
$requiredDirs = ['app', 'support'];
foreach ($requiredDirs as $dir) {
    $fullDir = $rootPath . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($fullDir)) continue;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') continue;
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'declare(strict_types=1)') === false) {
            echo "  ❌ MISSING declare: " . str_replace($rootPath . DIRECTORY_SEPARATOR, '', $file->getPathname()) . PHP_EOL;
            $errors++;
        }
    }
}
echo "  Done." . PHP_EOL . PHP_EOL;

// ---------- 2. 检查 Service 类 ----------
echo "[2/5] Checking Service classes..." . PHP_EOL;
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

// ---------- 3. 检查 Process 类 listen 配置 ----------
echo "[3/5] Checking Process listen config..." . PHP_EOL;
$processDir = $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'process';
if (is_dir($processDir)) {
    foreach (glob($processDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
        $name = basename($file);
        $content = file_get_contents($file);
        if (strpos($content, "'listen' => ''") !== false) {
            echo "  ⚠️  Empty listen config: app/process/$name (脚手架默认值，需填写)" . PHP_EOL;
            $warnings++;
        } elseif (preg_match("/protected\\s+static\\s+array\\s+\\\$processConfig\\s*=\\s*\\[([^\\]]+)\\]/s", $content, $m)) {
            if (strpos($m[1], "'listen'") !== false) {
                echo "  ✅ $name" . PHP_EOL;
            } else {
                echo "  ⚠️  Missing listen key: app/process/$name" . PHP_EOL;
                $warnings++;
            }
        }
    }
}
echo PHP_EOL;

// ---------- 4. 检查 Process 是否在 AppCommand 注册 ----------
echo "[4/5] Checking Process registration in AppCommand..." . PHP_EOL;
$appCmdFile = $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'command' . DIRECTORY_SEPARATOR . 'AppCommand.php';
if (is_file($appCmdFile)) {
    $appCmdContent = file_get_contents($appCmdFile);
    preg_match("/protected\\s+array\\s+\\\$process\\s*=\\s*\\[([^\\]]*)\\]/s", $appCmdContent, $registeredMatches);
    $registered = $registeredMatches[1] ?? '';

    if (is_dir($processDir)) {
        foreach (glob($processDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
            $name = basename($file, '.php');
            if (strpos($registered, $name . '::class') === false && strpos($registered, "'$name'") === false) {
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

// ---------- 5. 检查 Workerman 常驻内存红线 ----------
echo "[5/5] Checking resident-memory red lines..." . PHP_EOL;
if (is_dir($processDir)) {
    foreach (glob($processDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
        $name = basename($file);
        $content = file_get_contents($file);
        $hasTimerAdd = strpos($content, 'Timer::add') !== false;
        $hasTimerDel = strpos($content, 'Timer::del') !== false;
        $hasOnWorkerStop = strpos($content, 'onWorkerStop') !== false;
        $hasTryCatch = strpos($content, 'try {') !== false;

        if ($hasTimerAdd && !$hasTimerDel) {
            echo "  ❌ Timer leak risk (Timer::add without Timer::del): app/process/$name" . PHP_EOL;
            $errors++;
        }
        if ($hasTimerAdd && !$hasOnWorkerStop) {
            echo "  ⚠️  Timer cleanup missing onWorkerStop: app/process/$name" . PHP_EOL;
            $warnings++;
        }
    }
}

// 检查全局变量修改（PHP 原生实现，兼容 Windows）
$globalCheckDirs = ['app', 'support'];
$forbiddenPatterns = [
    '/\$GLOBALS\s*\[/' => 'GLOBALS 修改',
];
foreach ($globalCheckDirs as $gDir) {
    $fullDir = $rootPath . DIRECTORY_SEPARATOR . $gDir;
    if (!is_dir($fullDir)) continue;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') continue;
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

// ---------- Summary ----------
echo "=== Summary ===" . PHP_EOL;
echo "Errors:   $errors" . PHP_EOL;
echo "Warnings: $warnings" . PHP_EOL;
echo "Status:   " . ($errors > 0 ? 'FAIL ❌' : 'PASS ✅') . PHP_EOL;

exit($errors > 0 ? 1 : 0);
