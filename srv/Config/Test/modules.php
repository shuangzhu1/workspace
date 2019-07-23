<?php

// Register the installed modules
$application->registerModules(array(
    'wap' => array(
        'className' => 'Multiple\Wap\Module',
        'path' => __DIR__ . '/../../Apps/Wap/Module.php'
    ),
    'home' => array(
        'className' => 'Multiple\Home\Module',
        'path' => __DIR__ . '/../../Apps/Home/Module.php'
    ),
    'panel' => array(
        'className' => 'Multiple\Panel\Module',
        'path' => __DIR__ . '/../../Apps/Panel/Module.php'
    ),
    'developer' => array(
        'className' => 'Multiple\Developer\Module',
        'path' => __DIR__ . '/../../Apps/Developer/Module.php'
    ),
    'api' => array(
        'className' => 'Multiple\Api\Module',
        'path' => __DIR__ . '/../../Apps/Api/Module.php'
    ),
    'open' => array(
        'className' => 'Multiple\Open\Module',
        'path' => __DIR__ . '/../../Apps/Open/Module.php'
    ),
));
