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

use Exception;
use localzet\SocketIO\Debug;

class PollingXHR extends Polling
{
    public $sid = null;

    public function __construct()
    {
        Debug::debug('PollingXHR __construct');
    }

    public function __destruct()
    {
        Debug::debug('PollingXHR __destruct');
    }

    public function onRequest($req)
    {
        if ('OPTIONS' === $req->method) {
            $res = $req->res;
            $headers = $this->headers($req);
            $headers['Access-Control-Allow-Headers'] = 'Content-Type';
            $res->writeHead(200, '', $headers);
            $res->end();
        } else {
            parent::onRequest($req);
        }
    }

    public function doWrite($data)
    {
        // explicit UTF-8 is required for pages not served under utf todo
        $content_type = preg_match('/^\d+:/', $data) ? 'text/plain; charset=UTF-8' : 'application/octet-stream';
        $content_length = strlen($data);
        $headers = [
            'Content-Type' => $content_type,
            'Content-Length' => $content_length,
            'X-XSS-Protection' => '0',
        ];
        if (empty($this->res)) {
            echo new Exception('empty this->res');
            return;
        }
        $this->res->writeHead(200, '', $this->headers($this->req, $headers));
        $this->res->end($data);
    }

    public function headers(object $req, array $headers = []): array
    {
        if (isset($req->headers['origin'])) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
            $headers['Access-Control-Allow-Origin'] = $req->headers['origin'];
        } else {
            $headers['Access-Control-Allow-Origin'] = '*';
        }
        $listeners = $this->listeners('headers');
        foreach ($listeners as $listener) {
            $listener($headers);
        }
        return $headers;
    }
}
