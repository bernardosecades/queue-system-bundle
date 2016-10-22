<?php

/**
 * MIT License
 *
 * Copyright (c) 2016 Bernardo Secades
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace BernardoSecades\QueueSystemBundle\Driver;

use BernardoSecades\QueueSystemBundle\Exception\DriverConnectionException;
use Redis;

/**
 * RedisDriver.
 *
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class RedisDriver implements  DriverInterface
{
    /** @var  Redis */
    protected $client;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->client = new Redis();
        $connected = $this->client->connect($config['host'], $config['port']);

        if (!$connected) {
            throw new DriverConnectionException('Can not connect to Redis server');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue($nameQueue, $value)
    {
        return $this->client->lpush($nameQueue, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function dequeue($nameQueue)
    {
        return  $this->client->rPop($nameQueue);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue($nameQueue)
    {
        return (bool) $this->client->del($nameQueue);
    }

    /**
     * {@inheritdoc}
     */
    public function queueExist($nameQueue)
    {
        return $this->client->exists($nameQueue);
    }

    /**
     * {@inheritdoc}
     */
    public function count($nameQueue)
    {
        return $this->client->lLen($nameQueue);
    }
}
