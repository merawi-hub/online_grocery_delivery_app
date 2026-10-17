<?php
$lines = file(__DIR__ . '/storage/logs/laravel.log');
foreach (array_reverse($lines) as $line) {
    if (strpos($line, 'headers already sent') !== false || strpos($line, 'output started at') !== false) {
        echo $line . PHP_EOL;
    }
}
