<?php
$lines = file(__DIR__ . '/storage/logs/laravel.log');
for ($i = 10410; $i < 10465; $i++) {
    echo "$i: " . $lines[$i];
}
