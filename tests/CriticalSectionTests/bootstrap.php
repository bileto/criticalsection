<?php

declare(strict_types=1);

$autoloader = require_once __DIR__ . '/../../vendor/autoload.php';

const TEST_DIR = __DIR__;
define("TEMP_DIR", TEST_DIR . '/../tmp/' . (isset($_SERVER['argv']) ? hash('sha256', serialize($_SERVER['argv'])) : getmypid()));

Tester\Environment::setup();

return $autoloader;
