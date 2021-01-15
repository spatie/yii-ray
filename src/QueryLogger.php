<?php

namespace Spatie\YiiRay;

use Spatie\YiiRay\Payloads\ExecutedQueryPayload;
use Tightenco\Collect\Support\Collection;
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

        $messages = (new Collection($messages))
            ->filter(function ($message) {
                return $message[3] >= $this->started_at;
            });

        $messages->map(function ($message, $index) use ($messages) {
            // We only process messages that have an identical next message
            // The next index that has the same query, is the PROFILE_END query
            if (! isset($messages[$index + 1])) {
                return null;
            }

            $nextMessage = $messages[$index + 1] ?? null;
            if ($nextMessage[0] !== $message[0]) {
                return null;
            }

            return [
                'sql' => $message[0],
                'time' => round(($nextMessage[3] - $message[3]) * 1000, 2),
            ];
        })
        ->filter()
        ->each(function (array $query) {
            $payload = new ExecutedQueryPayload($query);

            Yii::$container->get(Ray::class)->sendRequest($payload);
        });
    }

    public function clear(): self
    {
        $this->messages = [];

        return $this;
    }
}
