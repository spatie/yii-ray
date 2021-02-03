<?php

namespace Spatie\YiiRay\Tests;

use Spatie\Ray\Settings\Settings;
use Spatie\YiiRay\BootstrapRay;
use Spatie\YiiRay\Ray;
use Spatie\YiiRay\Tests\TestClasses\FakeClient;
use Yii;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var \Spatie\YiiRay\Tests\TestClasses\FakeClient */
    protected $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        (new BootstrapRay())->bootstrap(Yii::$app);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new FakeClient();

        Yii::$container->set(Ray::class, function () {
            $settings = Yii::$container->get(Settings::class);

            $ray = new Ray($settings, $this->client, 'fakeUuid');

            if (! $settings->enable) {
                $ray->disable();
            }

            return $ray;
        });
    }
}
