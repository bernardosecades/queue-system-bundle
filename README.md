QueueSytem for Symfony
======================

### Simple queue system based on Redis

Installing/Configuring
----------------------

## Version

* Use alias of `dev-master` for last version.

## Installing

``` bash
wget http://download.redis.io/redis-stable.tar.gz
tar xvzf redis-stable.tar.gz
cd redis-stable
make
```

## Installing bundle

You have to add require line into you composer.json file

``` yml
"require": {
    ...
    "bernardosecades/queue-system-bundle": "dev-master"
},
```

Then you have to use composer to update your project dependencies

``` bash
php composer.phar update
```

And register the bundle in your appkernel.php file

``` php
return array(
    // ...
    new BernardoSecades\QueueSystemBundle\QueueSystemBundle(),
    // ...
);
```

## Configuration

In the current version, all conections are localhost:6379, but as soon as posible connections will be configurable, the same for events.
You need to configure all.
By default jms serializer has the value 'Json'. In next version you will can implement custom serializer.

``` yml
queue_system:
    # Queues definition
    queues:
        images: "queue:mail"
        api: "queue:api"

    # Server configuration. By default, these values
    server:
        redis:
            host: 127.0.0.1
            port: 6379
            database: ~
```

Jobs and Queues
---------------

Each queue you define in config.yml will create a queue service, for example if you define queue 'queue:api' you can access to queue
like a service with `$this->getContainer()->get('queue_system.queue_api')`, example:

`Producer`

```php
...
use BernardoSecades\QueueSystemBundle\Job\MessageDataJob;
...

/** @var \BernardoSecades\QueueSystemBundle\Queue\Queue $queue */
$queue = $this->getContainer()->get('queue_system.queue_api');

$message = new Message('queue_system.api_job', ['user_id' => 13567]);

$queue->enqueue($message);

```

In this example you should create a service API job extending of `BernardoSecades\QueueSystemBundle\Job\JobAbstract`, where
you will need implement the method `handle()` and `getDicName()`, the first parameter of object `MessageDataJob` is the service name
of your custom job.

```php
class ApiJob extends JobAbstract
{
    /**
     * Name service in your dependency injection container
     *
     * {@inheritdoc}
     */
    public function getDicName()
    {
        return 'queue_system.api_job';
    }

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $arguments = $this->getArguments();
        syslog(0, sprintf('Executing method handle with argument user_id: %d', $arguments['user_id']));
        ...
        // do more things
    }
}
```

Worker
------

This bundle include a worker command to consume jobs from queue saved in redis.

Example to consume jobs from queue `queue:api` you should execute the next command:

`./app/console queue-system:worker queue:api`

The command have options like:

- --worker-max-jobs=WORKER-MAX-JOBS Number of jobs to process [default: 0]
- --worker-tries-process-job=WORKER-TRIES-PROCESS-JOB Number of retries to process a job [default: 0]
- --worker-max-memory=WORKER-MAX-MEMORY Memory limit (Mb) [default: 0]
- --worker-sleep=WORKER-SLEEP In addition, you may specify the number of seconds to wait before polling for new jobs: [default: 0]

Todo
-----

- More tests
- Include event distpatcher.
- Include log system in worker command.
- Custom serializer.
- Fix code style to have compatibility with symfony style.
- Include external services to check quality code and fix possible issues.
- Improve the documentation and include example how set up worker in supervisord.