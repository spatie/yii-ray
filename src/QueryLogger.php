<?php

namespace Spatie\YiiRay;

use Spatie\YiiRay\Payloads\ExecutedQueryPayload;
use Yii;
use yii\log\Target;

class QueryLogger extends Target
{
    /** @var int|null */
    private $started_at;

    public function startLogginQueries(): self
    {
        $this->messages = [];
        $this->enabled = true;
        $this->started_at = microtime(true);

        return $this;
    }

    public function stopLoggingQueries(): self
    {
        Yii::$app->getLog()->getLogger()->flush();
        $this->enabled = false;
        $this->started_at = null;

        return $this;
    }

    public function export()
    {
        // We don't need to save the DB logs anywhere
    }

    public function collect($messages, $final)
    {
        if (! $this->enabled) {
            return;
        }

        $messages = static::filterMessages(
            $messages,
            $this->getLevels(),
            $this->categories,
            $this->except
        );

        $messages = array_values(array_filter($messages, function ($message) {
            return $message[3] >= $this->started_at;
        }));

        foreach ($messages as $index => $message) {
            if (! isset($messages[$index + 1])) {
                continue;
            }

            $nextMessage = $messages[$index + 1];
            if ($nextMessage[0] !== $message[0]) {
                continue;
            }

            $payload = new ExecutedQueryPayload([
                'sql' => $message[0],
                'time' => round(($nextMessage[3] - $message[3]) * 1000, 2),
            ]);

            Yii::$container->get(Ray::class)->sendRequest($payload);
        }
    }

    public function clear(): self
    {
        $this->messages = [];

        return $this;
    }
}
