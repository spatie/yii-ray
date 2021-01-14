<?php

namespace Spatie\YiiRay\Payloads;

use ReflectionProperty;
use Spatie\Ray\ArgumentConverter;
use Spatie\Ray\Payloads\Payload;
use yii\base\Event;

class EventPayload extends Payload
{
    protected Event $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function getType(): string
    {
        return 'event';
    }

    public function getContent(): array
    {
        $payload = [
            'name' => $this->event->name,
            'class' => get_class($this->event),
            'senderClass' => is_object($this->event->sender) ? get_class($this->event->sender) : $this->event->sender,
        ];

        $eventReflection = new \ReflectionClass($this->event);

        foreach ($eventReflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getName() !== 'sender') {
                $payload[$property->getName()] = $property->getValue($this->event);
            }
        }

        return [
            'name' => $this->event->name,
            'event' => ArgumentConverter::convertToPrimitive($payload),
            'class_based_event' => true,
        ];
    }
}
