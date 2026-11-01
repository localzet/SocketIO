<?php

/**
 * @package     Localzet Socket.IO
 * @link        https://github.com/localzet/SocketIO
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <creator@localzet.com>
*/

namespace localzet\SocketIO\Engine\Transports;

use localzet\SocketIO\Debug;

class PollingJsonp extends Polling
{
    public $head = null;
    public $foot = ');';

    public function __construct($req)
    {
        $this->head = '___eio[' . (isset($req['_query']['j']) ? preg_replace('/[^0-9]/', '', $req['_query']['j']) : '') . '](';
        Debug::debug('PollingJsonp __construct');
    }

    public function __destruct()
    {
        Debug::debug('PollingJsonp __destruct');
    }

    public function onData($data)
    {
        $parsed_data = null;
        parse_str($data, $parsed_data);
        $data = $parsed_data['d'];
        call_user_func([$this, 'parent::onData'], preg_replace('/\\\\n/', '\\n', $data));
    }

    public function doWrite($data): void
    {
        $js = json_encode($data);

        $data = $this->head . $js . $this->foot;

        // explicit UTF-8 is required for pages not served under utf
        $headers = [
            'Content-Type' => 'text/javascript; charset=UTF-8',
            'Content-Length' => strlen($data),
            'X-XSS-Protection' => '0'
        ];
        if (empty($this->res)) {
            echo new Exception('empty $this->res');
            return;
        }
        $this->res->writeHead(200, '', $this->headers($headers));
        $this->res->end($data);
    }

    public function headers(array $headers = []): array
    {
        $listeners = $this->listeners('headers');
        foreach ($listeners as $listener) {
            $listener($headers);
        }
        return $headers;
    }
}
