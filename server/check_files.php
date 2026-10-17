<?php
function scanDirectory($dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getRealPath());
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                echo "BOM detected: " . $file->getRealPath() . PHP_EOL;
            }
            if (substr($content, 0, 5) !== '<?php') {
                echo "Leading whitespace/chars: " . $file->getRealPath() . PHP_EOL;
            }
            if (strpos($content, '?>') !== false) {
                echo "Closing tag ?> found: " . $file->getRealPath() . PHP_EOL;
            }
        }
    }
}

scanDirectory(__DIR__ . '/app');
scanDirectory(__DIR__ . '/routes');
scanDirectory(__DIR__ . '/config');
scanDirectory(__DIR__ . '/bootstrap');
