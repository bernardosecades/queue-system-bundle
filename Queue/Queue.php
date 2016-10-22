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

namespace BernardoSecades\QueueSystemBundle\Queue;

use BernardoSecades\QueueSystemBundle\Driver\DriverInterface;
use BernardoSecades\QueueSystemBundle\Job\MessageDataJob;
use JMS\Serializer\SerializerInterface;

/**
 * Queue.
 *
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class Queue
{
    /** @var  string $name */
    protected $name;

    /** @var DriverInterface $driver */
    protected $driver;

    /** @var  SerializerInterface $serializer */
    protected $serializer;

    /**
     * @param string              $name
     * @param DriverInterface     $driver
     * @param SerializerInterface $serializer
     */
    public function __construct($name, DriverInterface $driver, SerializerInterface $serializer)
    {
        $this->name       = $name;
        $this->driver     = $driver;
        $this->serializer = $serializer;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param MessageDataJob $messageDataJob
     * @throw \RuntimeException
     *
     * @return int The length queue after doing enqueue operation
     */
    public function enqueue(MessageDataJob $messageDataJob)
    {
        $jsonMessageDataJob = $this->serializer->serialize($messageDataJob, 'json');

        return $this->driver->enqueue($this->getName(), $jsonMessageDataJob);
    }

    /**
     * Returns null if no element available in queue.
     *
     * @return MessageDataJob | null
     */
    public function dequeue()
    {
        $jsonMessageDataJob = $this->driver->dequeue($this->getName());

        if (null === $jsonMessageDataJob) {
            return null;
        }

        return $this->serializer->deserialize($jsonMessageDataJob, MessageDataJob::class, 'json');
    }

    /**
     * @param array $jobsData
     * @return int queue length after doing enqueue operation.
     */
    public function multiEnqueue(array $jobsData)
    {
        $currentQueueSize = 0;
        foreach ($jobsData as $jobData) {
            $currentQueueSize = $this->enqueue($jobData);
        }

        return $currentQueueSize;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->driver->count($this->getName());
    }
}
