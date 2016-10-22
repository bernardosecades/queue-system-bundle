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

namespace BernardoSecades\QueueSystemBundle\Command;

use BernardoSecades\QueueSystemBundle\Queue\Queue;
use BernardoSecades\QueueSystemBundle\Job\JobAbstract;
use BernardoSecades\QueueSystemBundle\Job\MessageDataJob;
use BernardoSecades\QueueSystemBundle\ValueObject\WorkerControlCodes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Exception;

/**
 * WorkerCommandAbstract.
 *
 * @author bernardosecades <bernardosecades@gmail.com>
 */
abstract class WorkerCommandAbstract extends Command implements ContainerAwareInterface
{
    /** @var string */
    protected $workerId;

    /** @var Queue */
    protected $queue;

    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $queueName;

    /** @var int */
    protected $maxJobs = 0;

    /** @var int */
    protected $maxMemory = 0;

    /** @var int */
    protected $jobsProcessed = 0;

    /** @var int */
    protected $triesProcessJob;

    /** @var int */
    protected $delay;

    /**
     * @param null|string $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->workerId = getmypid().get_called_class();
    }

    /**
     * @return mixed
     */
    abstract public function configureWorker();

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param string $queueName
     * @return WorkerCommandAbstract
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

        return $this;
    }

    final protected function configure()
    {
        $this
            ->addOption(
                'worker-max-jobs',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of jobs to process',
                0
            )
            ->addOption(
                'worker-tries-process-job',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of retries to process a job',
                0
            )
            ->addOption(
                'worker-max-memory',
                null,
                InputOption::VALUE_REQUIRED,
                'Memory limit (Mb)',
                0
            )
            ->addOption(
                'worker-sleep',
                null,
                InputOption::VALUE_REQUIRED,
                'In addition, you may specify the number of seconds to wait before polling for new jobs:',
                0
            )
            ->addOption(
                'worker-delay',
                null,
                InputOption::VALUE_REQUIRED,
                'Wait this number of seconds before processing jobs',
                0
            );

        $this->configureWorker();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->delay();

        do {
            $controlCode = $this->executeWorker();
        } while (WorkerControlCodes::WORKING === $controlCode);

        return $this->shutdown($controlCode);
    }

    /**
     * @return int
     */
    protected function executeWorker()
    {
        if ($this->checkExecution() !== WorkerControlCodes::WORKING) {
            return $this->checkExecution();
        }

        $messageDataJob = $this->getQueue()->dequeue();

        if (is_null($messageDataJob)) {
            $controlCode = WorkerControlCodes::NO_JOBS;
        } elseif (!$this->getContainer()->has($messageDataJob->getNameJob())) {
            $controlCode = WorkerControlCodes::UNDEFINED_JOB;
        } else {
            try {
                $controlCode = WorkerControlCodes::WORKING;
                $this->handleJob($messageDataJob);
            } catch (Exception $exception) {
                $controlCode = WorkerControlCodes::EXCEPTION;
                $this->handleException($exception, $messageDataJob);
            }
        }

        return $controlCode;
    }

    /**
     * @param MessageDataJob $messageDataJob
     *
     * @return bool
     */
    protected function existJob(MessageDataJob $messageDataJob)
    {
        return $this->getContainer()->has($messageDataJob->getNameJob());
    }

    /**
     * @param MessageDataJob $messageDataJob
     * @return JobAbstract
     */
    protected function getJob(MessageDataJob $messageDataJob)
    {
        /** @var JobAbstract $job */
        $job = $this->getContainer()->get($messageDataJob->getNameJob());
        $job->setArguments($messageDataJob->getDataJob());

        return $job;
    }

    /**
     * @param MessageDataJob $messageDataJob
     */
    protected function handleJob(MessageDataJob $messageDataJob)
    {
        $messageDataJob->increaseAttempts();
        $job = $this->getJob($messageDataJob);
        $job->handle();

        ++$this->jobsProcessed;
    }

    /**
     * @param Exception      $exception
     * @param MessageDataJob $messageDataJob
     */
    protected function handleException(Exception $exception, MessageDataJob $messageDataJob)
    {
        if ($messageDataJob->getAttempts() <= $this->triesProcessJob) {
            $this->saveInQueue($messageDataJob);
        }

        // TODO create log with errors
        unset($exception);
    }

    /**
     * @param MessageDataJob $messageDataJob
     */
    protected function saveInQueue(MessageDataJob $messageDataJob)
    {
        /* @var Queue $queue */
        $queue = $this->getQueue();
        $queue->enqueue($messageDataJob);
    }

    /**
     * @return int
     */
    protected function checkExecution()
    {
        $controlCode = $this->getCurrentControlCode();

        if (WorkerControlCodes::WORKING !== $controlCode) {
            return $controlCode;
        } elseif (!$this->getQueue() || 0 == $this->getQueue()->count()) {
            return WorkerControlCodes::NO_JOBS;
        } else {
            return WorkerControlCodes::WORKING;
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->maxJobs         = (int) $input->getOption('worker-max-jobs');
        $this->maxMemory       = (int) $input->getOption('worker-max-memory');
        $this->triesProcessJob = (int) $input->getOption('worker-tries-process-job');
        $this->delay           = (int) $input->getOption('worker-delay');
    }

    /**
     * @return bool
     */
    protected function getCurrentControlCode()
    {
        if ($this->maxJobs > 0 && $this->jobsProcessed >= $this->maxJobs) {
            return WorkerControlCodes::MAX_JOBS_REACHED;
        }

        $memory = memory_get_usage(true) / 1024 / 1024;

        if ($this->maxMemory > 0 && $memory > $this->maxMemory) {
            return WorkerControlCodes::MAX_MEMORY_REACHED;
        }

        return WorkerControlCodes::WORKING;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        if ($this->queue === null) {
            $this->queue = $this->getContainer()->get($this->queueName);
        }

        return $this->queue;
    }

    /**
     * @return string
     */
    protected function getQueueName()
    {
        return $this->queueName;
    }

    /***
     * @param int $exitCode
     * @return int
     */
    private function shutdown($exitCode)
    {
        if (in_array($exitCode, [WorkerControlCodes::WORKING, WorkerControlCodes::NO_JOBS])) {
            return 0;
        }

        return 1;
    }

    /**
     * @return void
     */
    private function delay()
    {
        if ($this->delay) {
            sleep($this->delay);
        }
    }
}