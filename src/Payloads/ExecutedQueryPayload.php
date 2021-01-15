<?php

namespace Spatie\YiiRay\Payloads;

use Spatie\Ray\Payloads\Payload;
use Yii;

class ExecutedQueryPayload extends Payload
{
    protected array $query;

    public function __construct(array $query)
    {
        $this->query = $query;
    }

    public function getType(): string
    {
        return 'executed_query';
    }

    public function getContent(): array
    {
        return [
            'sql' => $this->query['sql'],
            'bindings' => [],
            'connection_name' => Yii::$app->getDb()->getDriverName(),
            'time' => $this->query['time'],
        ];
    }
}
