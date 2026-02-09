<?php

namespace Spatie\YiiRay;

use Composer\InstalledVersions;
use Spatie\Ray\Client;
use Spatie\Ray\Ray as BaseRay;
use Spatie\Ray\Settings\Settings;
use Yii;

class Ray extends BaseRay
{
    public function __construct(Settings $settings, Client $client = null, string $uuid = null)
    {
        // persist the enabled setting across multiple instantiations
        $enabled = static::$enabled;

        parent::__construct($settings, $client, $uuid);

        static::$enabled = $enabled;
    }

    public function showEvents($callable = null): self
    {
        $wasLoggingEvents = $this->eventLogger()->isLoggingEvents();

        $this->eventLogger()->enable();

        if ($callable) {
            $callable();

            if (! $wasLoggingEvents) {
                $this->eventLogger()->disable();
            }
        }

        return $this;
    }

    public function events($callable = null): self
    {
        return $this->showEvents($callable);
    }

    public function showQueries($callable = null): self
    {
        $wasLoggingQueries = Yii::$container->get(QueryLogger::class)->enabled;

        if (! $wasLoggingQueries) {
            Yii::$container->get(QueryLogger::class)->startLogginQueries();
        }

        if (! is_null($callable)) {
            $callable();

            if (! $wasLoggingQueries) {
                $this->stopShowingQueries();
            }
        }

        return $this;
    }

    public function queries($callable = null): self
    {
        return $this->showQueries($callable = null);
    }

    public function stopShowingQueries(): self
    {
        Yii::$container->get(QueryLogger::class)->stopLoggingQueries();

        return $this;
    }

    public function stopShowingEvents(): self
    {
        /** @var \Spatie\YiiRay\EventLogger $eventLogger */
        $eventLogger = Yii::$container->get(EventLogger::class);
        $eventLogger->disable();

        return $this;
    }

    protected function eventLogger(): EventLogger
    {
        return Yii::$container->get(EventLogger::class);
    }

    /**
     * @param  \Spatie\Ray\Payloads\Payload|\Spatie\Ray\Payloads\Payload[]  $payloads
     *
     * @throws \Exception
     */
    public function sendRequest($payloads, array $meta = []): BaseRay
    {
        if (! $this->enabled()) {
            return $this;
        }

        $meta = [
            'yii_version' => Yii::getVersion(),
        ];

        if (class_exists(InstalledVersions::class)) {
            $meta['yii_ray_package_version'] = InstalledVersions::getVersion('spatie/yii-ray');
        }

        return BaseRay::sendRequest($payloads, $meta);
    }
}
