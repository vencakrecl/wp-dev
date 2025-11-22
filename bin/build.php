<?php

$pharFile = __DIR__ . '/../dist/wp-dev.phar';

// Create dist folder
if(!is_dir(__DIR__ . '/../dist')) {
    mkdir(__DIR__ . '/../dist');
}

// Remove old phar if exists
if (file_exists($pharFile)) {
    unlink($pharFile);
}

$phar = new Phar($pharFile);
$phar->startBuffering();

// Add all files
$it = new AppendIterator();
$it->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src')));
$it->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../vendor')));

/** @var SplFileInfo $file */
foreach ($it as $file) {
    if ($file->isFile()) {
        $phar->addFile($file->getRealPath(), str_replace(dirname(__DIR__), '', $file->getRealPath()));
    }
}

$phar->addFile(__DIR__ . '/wp-dev.php', 'bin/wp-dev.php');

// Set the default stub (entry point)
$stub = $phar->createDefaultStub('bin/wp-dev.php');
$phar->setStub($stub);

// Compress files
$phar->compressFiles(Phar::GZ);

$phar->stopBuffering();

echo "PHAR created: $pharFile\n";