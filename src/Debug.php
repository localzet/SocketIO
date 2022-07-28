<?php

/**
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

namespace localzet\SocketIO;

class Debug
{
    public static function debug($var)
    {
        global $debug;
        if ($debug)
            echo var_export($var, true) . "\n";
    }
}
