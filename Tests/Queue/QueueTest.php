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

namespace BernardoSecades\QueueSystemBundle\Tests\Queue;

use BernardoSecades\QueueSystemBundle\Queue\Queue;
use BernardoSecades\QueueSystemBundle\Job\MessageDataJob;
use BernardoSecades\QueueSystemBundle\Driver\DriverInterface;
use JMS\Serializer\SerializerInterface;

/**
 * Queue test.
 *
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class QueueTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Queue */
    protected $queue;

    /** @var  DriverInterface */
    protected $driver;

    /** @var  SerializerInterface  */
    protected $serializer;

    /** @var  string $queueName */
    protected $queueName;


    public function setUp()
    {
        $this->queueName  = 'queue:test';
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->driver     = $this->prophesize(DriverInterface::class);
        $this->queue      = new Queue($this->queueName, $this->driver->reveal(), $this->serializer->reveal());
    }

    public function testQueueClass()
    {
        $this->assertInstanceOf(Queue::class, $this->queue);
    }

    public function testQueueName()
    {
        $this->assertEquals('queue:test', $this->queue->getName());
    }

    public function testEnqueue()
    {
        $messageDataJob = new MessageDataJob('name_job', ['name' => 'Test']);

        $this->serializer->serialize($messageDataJob, 'json')->willReturn(serialize($messageDataJob));
        $this->queue->enqueue($messageDataJob);
        $this->driver->count($this->queueName)->willReturn(1);

        $this->assertEquals(1, $this->queue->count());
    }

    public function testMultiEnqueue()
    {
        $jobsData = [
            0 => new MessageDataJob('name_job1', ['name' => 'Test1']),
            1 => new MessageDataJob('name_job2', ['name' => 'Test2'])
        ];

        $this->serializer->serialize($jobsData[0], 'json')->willReturn(serialize($jobsData[0]));
        $this->serializer->serialize($jobsData[1], 'json')->willReturn(serialize($jobsData[1]));
        $this->queue->multiEnqueue($jobsData);
        $this->driver->count($this->queueName)->willReturn(2);

        $this->assertEquals(2, $this->queue->count());
    }

    public function testDequeue()
    {
        $messageDataJob = new MessageDataJob('name_job', ['name' => 'Test']);

        $objectSerialized = serialize($messageDataJob);
        $this->serializer->serialize($messageDataJob, 'json')->willReturn($objectSerialized);
        $this->driver->enqueue($this->queueName, $objectSerialized)->willReturn(1);

        $this->driver->dequeue($this->queueName, null)->willReturn($objectSerialized);
        $this->serializer
            ->deserialize($objectSerialized, MessageDataJob::class, 'json')
            ->willReturn($messageDataJob);

        $messageDataJob2 = $this->queue->dequeue();

        $this->assertSame($messageDataJob, $messageDataJob2);
    }

    public function testDequeueWithNoElementsInQueue()
    {
        $this->driver->dequeue($this->queueName, null)->willReturn(null);
        $this->assertSame(null, $this->queue->dequeue());
    }

    public function testCount()
    {
        $messageDataJob = new MessageDataJob('name_job', ['name' => 'Test']);

        $objectSerialized = serialize($messageDataJob);
        $this->serializer->serialize($messageDataJob, 'json')->willReturn($objectSerialized);
        $this->driver->enqueue($this->queueName, $objectSerialized)->willReturn(1);

        $this->driver->count($this->queue->getName())->willReturn(1);

        $this->assertEquals(1, $this->queue->count());
    }
}
