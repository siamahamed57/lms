<?php
// Start session


// Load environment variables (optional)
if(file_exists(__DIR__ . '/.env')){
    $env = parse_ini_file(__DIR__ . '/.env');
}

// Load database config
require_once __DIR__ . '/includes/db.php';

// Autoload classes (simple PSR-4 style)
spl_autoload_register(function($class){
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    if(file_exists($file)){
        require_once $file;
    }
});

// Load routes
require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/api.php';