<?php
// 模板占位符替换验证：将 {{NAME}} 等占位符替换后应能通过 php -l
$templates = [
    ['service-skeleton.php', 'app/service/TestDemoService.php', ['{{NAME}}' => 'TestDemo', '{{CONFIG_KEY}}' => 'test_demo', '{{DATE}}' => '2026-10-19']],
    ['process-skeleton.php', 'app/process/TestDemoProcess.php', ['{{NAME}}' => 'TestDemo', '{{LISTEN}}' => 'tcp://0.0.0.0:9000', '{{DATE}}' => '2026-10-19']],
    ['command-skeleton.php', 'app/command/TestDemoCommand.php', ['{{NAME}}' => 'TestDemo', '{{DEFAULT_NAME}}' => 'test:demo', '{{DESCRIPTION}}' => 'test', '{{DATE}}' => '2026-10-19']],
    ['config-skeleton.php', 'config-test.php', ['{{CONFIG_DESC}}' => 'test', '{{KEY}}' => 'key', '{{VALUE}}' => 'val']],
    ['queue-consumer.php', 'app/queue/TestDemoQueue.php', ['{{NAME}}' => 'TestDemo', '{{QUEUE_NAME}}' => 'test-demo', '{{DESCRIBE}}' => 'test', '{{DATE}}' => '2026-10-19']],
    ['crontab-task.php', 'app/crontab/TestDemoTask.php', ['{{NAME}}' => 'TestDemo', '{{DATE}}' => '2026-10-19']],
    ['dao.php', 'app/dao/TestDemoDao.php', ['{{NAME}}' => 'TestDemo', '{{MODULE}}' => 'product', '{{DESC}}' => '产品', '{{TABLE}}' => 'test_demo', '{{DATE}}' => '2026-10-19']],
    ['http-controller.php', 'app/http/controller/TestDemoController.php', ['{{NAME}}' => 'TestDemo', '{{MODULE}}' => 'admin', '{{DESC}}' => '演示', '{{DATE}}' => '2026-10-19']],
    ['route-file.php', 'app/http/routes/test-demo.php', ['{{NAME}}' => 'TestDemo', '{{MODULE}}' => 'admin', '{{DESC}}' => '演示', '{{PREFIX}}' => 'test-demo']],
    ['validate.php', 'app/validate/TestDemoValidate.php', ['{{NAME}}' => 'TestDemo', '{{MODULE}}' => 'product', '{{DESC}}' => '演示', '{{DATE}}' => '2026-10-19']],
    ['enum.php', 'app/contract/enum/TestDemoEnum.php', ['{{NAME}}' => 'TestDemo', '{{DESC}}' => '演示', '{{DATE}}' => '2026-10-19']],
    ['http-middleware.php', 'app/http/middleware/TestDemoMiddleware.php', ['{{NAME}}' => 'TestDemo', '{{MODULE}}' => 'admin', '{{DATE}}' => '2026-10-19']],
    ['process-plugin.php', 'app/process/TestDemoProcess.php', ['{{NAME}}' => 'TestDemo', '{{DATE}}' => '2026-10-19']],
];

$srcDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets';
$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gaia_tpl_test';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0777, true);
}

$fail = 0;
foreach ($templates as [$tpl, $dstName, $map]) {
    $content = file_get_contents($srcDir . DIRECTORY_SEPARATOR . $tpl);
    $content = str_replace(array_keys($map), array_values($map), $content);
    $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . basename($dstName);
    file_put_contents($tmpFile, $content);

    exec('php -l ' . escapeshellarg($tmpFile) . ' 2>&1', $out, $code);
    $status = $code === 0 ? 'PASS' : 'FAIL';
    echo "[$status] $tpl -> " . ($code === 0 ? 'valid PHP' : implode(' | ', $out)) . PHP_EOL;
    if ($code !== 0) $fail++;
    $out = [];
}

// 清理
foreach (glob($tmpDir . DIRECTORY_SEPARATOR . '*') as $f) unlink($f);
rmdir($tmpDir);

exit($fail > 0 ? 1 : 0);
