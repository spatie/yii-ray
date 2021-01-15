<?php

namespace Spatie\YiiRay;

use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use Spatie\Ray\Origin\DefaultOriginFactory;
use Spatie\Ray\Origin\Origin;
use Spatie\Ray\Ray;
use Tightenco\Collect\Support\Collection;
use yii\base\Component;
use yii\base\Event;

class OriginFactory extends DefaultOriginFactory
{
    public function getOrigin(): Origin
    {
        $frame = $this->getFrame();

        return new Origin(
            $frame->file ?? null,
            $frame->lineNumber ?? null,
        );
    }

    protected function getFrame(): ?Frame
    {
        $frames = (new Collection(Backtrace::create()->frames()))->reverse();

        $indexOfRay = $frames->search(function (Frame $frame) {
            if ($frame->class === Ray::class) {
                return true;
            }

            if (str_starts_with($frame->file, __DIR__)) {
                return true;
            }

            return false;
        });

        /** @var Frame|null $rayFrame */
        $rayFrame = $frames[$indexOfRay] ?? null;

        $rayFunctionFrame = $frames[$indexOfRay + 2] ?? null;

        /** @var Frame|null $foundFrame */
        $originFrame = $frames[$indexOfRay + 1] ?? null;

        if ($originFrame && str_ends_with($originFrame->file, Ray::makePathOsSafe('ray/src/helpers.php'))) {
            $framesAbove = 2;

            if ($rayFunctionFrame && $rayFunctionFrame->method === 'rd') {
                $framesAbove = 3;
            }

            $originFrame = $frames[$indexOfRay + $framesAbove] ?? null;
        }

        if (! $rayFrame) {
            return null;
        }

        if ($originFrame && str_ends_with($originFrame->file, Ray::makePathOsSafe('ray/src/helpers.php'))) {
            $originFrame = $frames[$indexOfRay + 2] ?? null;
        }

        if (is_null($originFrame->class) && $originFrame->method === 'call_user_func') {
            $originFrame = $frames[$indexOfRay + 2] ?? null;
        }

        if ($originFrame->class === Event::class) {
            return $this->findFrameForEvent($frames);
        }

        return $originFrame;
    }

    protected function findFrameForEvent(Collection $frames): ?Frame
    {
        $indexOfComponentCall = $frames
            ->search(function (Frame $frame) {
                return $frame->class === Component::class;
            });

        /** @var Frame $foundFrame */
        $foundFrame = $frames[$indexOfComponentCall + 1];

        return $foundFrame ?? null;
    }
}
