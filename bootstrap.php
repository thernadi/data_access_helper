<?php
// bootstrap.php

//declare(strict_types=1);

// PHP version check
if (PHP_VERSION_ID < 70400) {
    die("PHP 7.4 or higher is required.");
}

// Polyfills
require_once __DIR__ ."/src/compat/poly.php";

?>