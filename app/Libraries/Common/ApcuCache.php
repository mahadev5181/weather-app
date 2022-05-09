<?php

/**
 * This file is a part of the Phystrix library
 *
 * Copyright 2013-2014 oDesk Corporation. All Rights Reserved.
 *
 * This file is licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Libraries\Common;

use Exception;
use Psr\SimpleCache\CacheInterface;

/**
 * Object for request caching, one instance shared between all commands
 */
class ApcuCache implements CacheInterface {

    function clear() {
        throw new Exception('Not supported');
    }

    function delete($key) {
        apcu_delete($key);
    }

    function get($key, $default = null) {

        $response = apcu_fetch($key, $success);
        return $success ? $response : $default;

    }

    function set($key, $value, $ttl = 10) {
        apcu_store($key, $value, $ttl);
    }

    function has($key) {
        return apcu_exists($key);
    }

    function getMultiple($keys, $default = null) {
        return apcu_fetch($key);
    }

    function setMultiple($values, $ttl = 10) {
        apcu_store(array_keys($values), array_values($values), $ttl);
    }

    function deleteMultiple($keys) {
        apcu_delete($keys);
    }

}
