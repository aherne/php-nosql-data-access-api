<?php
require __DIR__ . '/vendor/autoload.php';
if (extension_loaded("apcu") && function_exists("apcu_enabled") && !apcu_enabled()) {
    echo "ERROR: APCu is installed but disabled for CLI.".PHP_EOL;
    echo "Run: php -d apc.enable_cli=1 test.php".PHP_EOL;
    exit(1);
}
try {
    new Lucinda\UnitTest\ConsoleController("unit-tests.xml", "local");
} catch (\Throwable $e) {
    echo "ERROR: ".get_class($e).PHP_EOL;
    if ($e->getMessage()) {
        echo "MESSAGE: ".$e->getMessage().PHP_EOL;
    }
    echo "LOCATION: ".$e->getFile().":".$e->getLine().PHP_EOL;
    echo "TRACE: ".$e->getTraceAsString().PHP_EOL;
    exit(1);
}
