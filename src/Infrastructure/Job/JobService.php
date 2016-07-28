<?php

namespace Honeybee\Infrastructure\Job;

use Closure;
use Exception;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\DataAccess\Connector\RabbitMqConnector;
use Honeybee\Infrastructure\Event\FailedJobEvent;
use Honeybee\Infrastructure\Event\Bus\Channel\ChannelMap;
use Honeybee\ServiceLocatorInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class JobService implements JobServiceInterface
{
    const DEFAULT_JOB = 'honeybee.jobs.execute_handlers';

    protected $connector;

    protected $service_locator;

    protected $job_map;

    protected $config;

    protected $logger;

    protected $channel;

    public function __construct(
        RabbitMqConnector $connector,
        ServiceLocatorInterface $service_locator,
        JobMap $job_map,
        ConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->service_locator = $service_locator;
        $this->job_map = $job_map;
        $this->connector = $connector;
        $this->logger = $logger;
    }

    public function dispatch(JobInterface $job, $exchange_name)
    {
        $message_payload = json_encode($job->toArray());
        $message = new AMQPMessage($message_payload, [ 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT ]);

        $routing_key = $job->getSettings()->get('routing_key');
        $this->getChannel()->basic_publish($message, $exchange_name, $routing_key);
    }

    public function consume($queue_name, Closure $message_callback)
    {
        $channel = $this->getChannel();

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queue_name, false, true, false, false, false, $message_callback);

        return $channel;
    }

    public function retry(JobInterface $job, $exchange_name, array $metadata = [])
    {
        $job_state = $job->toArray();
        $job_state['metadata']['retries'] = isset($job_state['metadata']['retries'])
            ? ++$job_state['metadata']['retries'] : 1;

        $message = new AMQPMessage(
            json_encode($job_state),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'expiration' => $job->getStrategy()->getRetryInterval()
            ]
        );

        $routing_key = $job->getSettings()->get('routing_key');
        $this->getChannel()->basic_publish($message, $exchange_name, $routing_key);
    }

    public function fail(JobInterface $job, $exchange_name, array $metadata = [])
    {
        $failed_job = $this->createJob(
            [
                'event' => new FailedJobEvent([
                    'failed_job_state' => $job->toArray(),
                    'metadata' => $metadata
                ]),
                'channel' => ChannelMap::CHANNEL_FAILED
            ]
        );

        $message = new AMQPMessage(
            json_encode($failed_job->toArray()),
            [ 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT ]
        );

        $routing_key = $job->getSettings()->get('routing_key');
        $this->getChannel()->basic_publish($message, $exchange_name, $routing_key);
    }

    public function createJob(array $job_state, $job_name = self::DEFAULT_JOB)
    {
        $job_config = $this->getJob($job_name);
        $strategy_config = $job_config['strategy'];
        $service_locator = $this->service_locator;

        $strategy_callback = function (JobInterface $job) use ($service_locator, $strategy_config) {
            $strategy_implementor = $strategy_config['implementor'];

            $retry_strategy = $service_locator->createEntity(
                $strategy_config['retry']['implementor'],
                [ ':job' => $job, ':settings' => $strategy_config['retry']['settings'] ]
            );

            $failure_strategy = $service_locator->createEntity(
                $strategy_config['failure']['implementor'],
                [ ':job' => $job, ':settings' => $strategy_config['failure']['settings'] ]
            );

            return new $strategy_implementor($retry_strategy, $failure_strategy);
        };

        return $this->service_locator->createEntity(
            $job_config['class'],
            [
                // job class cannot be overridden by state
                ':state' => [ Job::OBJECT_TYPE => $job_config['class'] ] + $job_state,
                ':strategy_callback' => $strategy_callback,
                ':settings' => $job_config['settings']
            ]
        );
    }

    public function getJobMap()
    {
        return $this->job_map;
    }

    public function getJob($job_name)
    {
        $job_config = $this->job_map->get($job_name);

        if (!$job_config) {
            throw new RuntimeError(sprintf('Configuration for job "%s" was not found.', $job_name));
        }

        return $job_config;
    }

    protected function getChannel()
    {
        if (!$this->channel) {
            $this->channel = $this->connector->getConnection()->channel();
        }
        return $this->channel;
    }
}
