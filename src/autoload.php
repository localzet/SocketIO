<?php

/**
 * @version     1.0.0-dev
 * @package     SocketIO Engine
 * @link        https://localzet.gitbook.io
 * 
 * @author      localzet <creator@localzet.ru>
 * 
 * @copyright   Copyright (c) 2018-2020 Zorin Projects 
 * @copyright   Copyright (c) 2020-2022 NONA Team
 * 
 * @license     https://www.localzet.ru/license GNU GPLv3 License
 */

spl_autoload_register(function ($name) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $name);
    $path = str_replace('localzet' . DIRECTORY_SEPARATOR . 'SocketIO', '', $path);
    if (is_file($class_file = __DIR__ . "/$path.php")) {
        require_once($class_file);
        if (class_exists($name, false)) {
            return true;
        }
    }
    return false;
});
