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

namespace localzet\SocketIO\Engine\Protocols\Http;

class Request
{
    public $onData = null;

    public $onEnd = null;

    public $httpVersion = null;

    public $headers = [];

    public $rawHeaders = null;

    public $method = null;

    public $url = null;

    public $connection = null;

    public $res = null;

    public $cleanup = null;

    public function __construct($connection, $raw_head)
    {
        $this->connection = $connection;
        $this->parseHead($raw_head);
    }

    public function parseHead($raw_head)
    {
        $header_data = explode("\r\n", $raw_head);
        list($this->method, $this->url, $protocol) = explode(' ', $header_data[0]);
        list($null, $this->httpVersion) = explode('/', $protocol);
        unset($header_data[0]);
        foreach ($header_data as $content) {
            if (empty($content)) {
                continue;
            }
            $this->rawHeaders[] = $content;
            list($key, $value) = explode(':', $content, 2);
            $this->headers[strtolower($key)] = trim($value);
        }
    }

    public function destroy()
    {
        $this->onData = $this->onEnd = $this->onClose = null;
        $this->connection = null;
    }
}
