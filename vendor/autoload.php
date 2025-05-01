<?php

spl_autoload_register(function ($class) {
    // Convert namespace to path
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    // Check PhpSpreadsheet namespace
    if (strpos($class, 'PhpOffice\\PhpSpreadsheet') === 0) {
        $file = __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/' . substr($class, strlen('PhpOffice\\PhpSpreadsheet')) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
    
    // Check PSR SimpleCache namespace
    if (strpos($class, 'Psr\\SimpleCache') === 0) {
        $file = __DIR__ . '/psr/simple-cache/src/' . substr($class, strlen('Psr\\SimpleCache')) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
    
    // Check Composer Pcre namespace
    if (strpos($class, 'Composer\\Pcre') === 0) {
        $file = __DIR__ . '/composer/pcre.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
