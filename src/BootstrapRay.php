<?php

namespace Spatie\YiiRay;

use Spatie\Ray\Client;
use Spatie\Ray\Payloads\Payload;
use Spatie\Ray\Settings\Settings;
use Spatie\Ray\Settings\SettingsFactory;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;

class BootstrapRay implements BootstrapInterface
{
    /** @var \yii\base\Application */
    private $app;

    public function bootstrap($app)
    {
        $this->app = $app;

        $this
            ->registerSettings()
            ->registerBindings()
            ->listenForEvents()
        ;
    }

    protected function registerSettings(): self
    {
        Yii::$container->setSingleton(Settings::class, function () {
            $settings = SettingsFactory::createFromConfigFile($this->app->getBasePath() . '/config');

            return $settings->setDefaultSettings([
                'enable' => YII_ENV_DEV,
                'send_log_calls_to_ray' => true,
                'send_dumps_to_ray' => true,
            ]);
        });

        return $this;
    }

    protected function registerBindings(): self
    {
        $settings = Yii::$container->get(Settings::class);

        Yii::$container->set(Client::class, fn () => new Client($settings->port, $settings->host));

        Yii::$container->set(Ray::class, function () {
            $client = Yii::$container->get(Client::class);
            $settings = Yii::$container->get(Settings::class);

            $ray = new Ray($settings, $client);

            if (! $settings->enable) {
                $ray->disable();
            }

            return $ray;
        });

        Payload::$originFactoryClass = OriginFactory::class;

        return $this;
    }

    protected function listenForEvents(): self
    {
        Yii::$container->setSingleton(EventLogger::class, fn () => new EventLogger());

        Event::on('*', '*', function ($event) {
            Yii::$container->get(EventLogger::class)->handleEvent($event);
        });

        return $this;
    }
}
