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

        $count = count($messages);

        for ($i = 0; $i < $count - 1; $i++) {
            $nextMessage = $messages[$i + 1];

            if ($nextMessage[0] !== $messages[$i][0]) {
                continue;
            }

            $payload = new ExecutedQueryPayload([
                'sql' => $messages[$i][0],
                'time' => round(($nextMessage[3] - $messages[$i][3]) * 1000, 2),
            ]);

            Yii::$container->get(Ray::class)->sendRequest($payload);

            $i++;
        }
    }

    public function clear(): self
    {
        $this->messages = [];

        return $this;
    }
}
