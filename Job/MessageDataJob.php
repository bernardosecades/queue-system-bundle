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

namespace BernardoSecades\QueueSystemBundle\Job;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

/**
 * MessageDataJob.
 *
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class MessageDataJob
{
    /**
     * @Type("string")
     * @Accessor(getter="getNameJob",setter="setNameJob")
     */
    protected $nameJob;

    /**
     * @Type("array")
     * @Accessor(getter="getDataJob",setter="setDataJob")
     */
    protected $dataJob = [];

    /**
     * @Type("integer")
     * @Accessor(getter="getAttempts",setter="setAttempts")
     */
    protected $attempts = 0;

    /**
     * @param string $nameJob
     * @param array  $dataJob
     */
    public function __construct($nameJob, array $dataJob = [])
    {
        $this->nameJob = $nameJob;
        $this->dataJob = $dataJob;
    }

    /**
     * @return string
     */
    public function getNameJob()
    {
        return $this->nameJob;
    }

    /**
     * @param string $nameJob
     */
    public function setNameJob($nameJob)
    {
        $this->nameJob = $nameJob;
    }

    /**
     * @return array
     */
    public function getDataJob()
    {
        return $this->dataJob;
    }

    /**
     * @param array $dataJob
     */
    public function setDataJob(array $dataJob)
    {
        $this->dataJob = $dataJob;
    }

    /**
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * @param int $attempts
     *
     * @return int
     */
    public function setAttempts($attempts)
    {
        $this->attempts = $attempts;
    }

    /**
     * @return int
     */
    public function increaseAttempts()
    {
        return ++$this->attempts;
    }
}