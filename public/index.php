<?php
file_put_contents('/tmp/debug.log', $_SERVER['REQUEST_URI'] . PHP_EOL, FILE_APPEND);


use App\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
