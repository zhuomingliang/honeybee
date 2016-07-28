<?php

namespace Honeybee\Infrastructure\Event\Bus\Transport;

use Honeybee\Infrastructure\Event\EventInterface;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Job\JobServiceInterface;

class JobQueueTransport extends EventTransport
{
    protected $job_service;

    protected $exchange;

    public function __construct($name, JobServiceInterface $job_service, $exchange)
    {
        parent::__construct($name);

        $this->exchange = $exchange;
        $this->job_service = $job_service;
    }

    public function send($channel_name, EventInterface $event, $subscription_index, SettingsInterface $settings = null)
    {
        $settings = $settings ?: new Settings;

        $job = $this->job_service->createJob(
            [
                'event' => $event,
                'channel' => $channel_name,
                'subscription_index' => $subscription_index
            ],
            $settings->get('job')
        );

        $this->job_service->dispatch($job, $this->exchange);
    }
}
