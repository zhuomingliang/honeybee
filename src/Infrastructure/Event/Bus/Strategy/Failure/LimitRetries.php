<?php

namespace Honeybee\Infrastructure\Event\Bus\Strategy\Failure;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Job\JobInterface;

class LimitRetries implements FailureStrategyInterface
{
    protected $limit;

    public function __construct(SettingsInterface $settings = null)
    {
        $settings = $settings ?: new Settings;

        $this->limit = (int)$settings->get('limit');
    }

    public function hasFailed(JobInterface $job)
    {
        $meta_data = $job->getMetaData();
        $retries = isset($meta_data['retries']) ? $meta_data['retries'] : 0;
        return $retries >= $this->limit;
    }
}
