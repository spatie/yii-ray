<?php

namespace Spatie\YiiRay;

use Spatie\YiiRay\Payloads\EventPayload;
use Yii;
use yii\base\Event;

class EventLogger
{
    protected bool $enabled = false;

    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    public function handleEvent(Event $event): void
    {
        if (! $this->shouldHandleEvent($event)) {
            return;
        }

        $payload = new EventPayload($event);

        Yii::$container->get(Ray::class)->sendRequest($payload);
    }

    public function isLoggingEvents(): bool
    {
        return $this->enabled;
    }

    protected function shouldHandleEvent(Event $event): bool
    {
        return $this->enabled;
    }
}
