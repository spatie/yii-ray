<?php

namespace Spatie\YiiRay\Tests;

use Spatie\Ray\Settings\Settings;
use Spatie\Snapshots\MatchesSnapshots;
use Spatie\YiiRay\Tests\TestClasses\TestEvent;
use Yii;
use yii\helpers\ArrayHelper;

class RayTest extends TestCase
{
    use MatchesSnapshots;

    /** @test */
    public function when_disabled_nothing_will_be_sent_to_ray()
    {
        Yii::$container->get(Settings::class)->enable = false;

        ray('test');

        // Enable for future tests
        ray()->enable();

        $this->assertCount(0, $this->client->sentPayloads());
    }

    /** @test */
    public function it_will_not_blow_up_when_not_passing_anything()
    {
        ray();

        $this->assertCount(0, $this->client->sentPayloads());
    }

    /** @test */
    public function it_can_be_disabled()
    {
        ray()->disable();
        ray('test');
        $this->assertCount(0, $this->client->sentPayloads());

        ray()->enable();
        ray('not test');
        $this->assertCount(1, $this->client->sentPayloads());
    }

    /** @test */
    public function it_can_check_enabled_status()
    {
        ray()->disable();
        $this->assertEquals(false, ray()->enabled());

        ray()->enable();
        $this->assertEquals(true, ray()->enabled());
    }

    /** @test */
    public function it_can_check_disabled_status()
    {
        ray()->disable();
        $this->assertEquals(true, ray()->disabled());

        ray()->enable();
        $this->assertEquals(false, ray()->disabled());
    }

    /** @test */
    public function it_can_send_a_class_based_event_to_ray()
    {
        ray()->enable();

        ray()->showEvents();

        Yii::$app->trigger('test-event', new TestEvent());

        ray()->stopShowingEvents();

        Yii::$app->trigger('test-event', new TestEvent());

        $this->assertCount(1, $this->client->sentPayloads());
        $this->assertEquals('test-event', ArrayHelper::getValue($this->client->sentPayloads(), '0.payloads.0.content.name'));
        $this->assertTrue(ArrayHelper::getValue($this->client->sentPayloads(), '0.payloads.0.content.class_based_event'));
    }

    /** @test */
    public function it_will_not_send_any_events_if_it_is_not_enabled()
    {
        Yii::$app->trigger('test-event', new TestEvent());

        $this->assertCount(0, $this->client->sentPayloads());
    }

    /** @test */
    public function the_show_events_function_accepts_a_callable()
    {
        ray()->enable();

        Yii::$app->trigger('start-event', new TestEvent());

        ray()->showEvents(function () {
            Yii::$app->trigger('event in callable', new TestEvent());
        });

        Yii::$app->trigger('end-event', new TestEvent());

        $this->assertCount(1, $this->client->sentPayloads());
        $this->assertEquals('event in callable', ArrayHelper::getValue($this->client->sentPayloads(), '0.payloads.0.content.name'));
    }

    /** @test */
    public function it_can_start_logging_queries()
    {
        Yii::$app->db->createCommand('CREATE TABLE if not exists elements (
            id INTEGER PRIMARY KEY
        )')->query();

        ray()->enable();
        ray()->showQueries();

        Yii::$app->db->createCommand('SELECT * FROM elements limit 1')->queryAll();

        ray()->stopShowingQueries();

        $this->assertCount(1, $this->client->sentPayloads());
    }

    /** @test */
    public function it_can_start_logging_queries_using_alias()
    {
        Yii::$app->db->createCommand('CREATE TABLE if not exists elements (
            id INTEGER PRIMARY KEY
        )')->query();

        ray()->enable();
        ray()->queries();

        Yii::$app->db->createCommand('SELECT * FROM elements limit 1')->queryAll();

        ray()->stopShowingQueries();

        $this->assertCount(1, $this->client->sentPayloads());
    }

    /** @test */
    public function it_can_stop_logging_queries()
    {
        Yii::$app->db->createCommand('CREATE TABLE if not exists elements (
            id INTEGER PRIMARY KEY
        )')->query();

        ray()->enable();
        ray()->showQueries();

        Yii::$app->db->createCommand('SELECT * FROM elements limit 1')->queryAll();
        Yii::$app->db->createCommand('SELECT * FROM elements limit 1')->queryAll();

        ray()->stopShowingQueries();

        $this->assertCount(2, $this->client->sentPayloads());

        Yii::$app->db->createCommand('SELECT * FROM elements limit 1')->queryAll();
        $this->assertCount(2, $this->client->sentPayloads());
    }

    /** @test */
    public function calling_log_queries_twice_will_not_log_all_queries_twice()
    {
        Yii::$app->db->createCommand('CREATE TABLE if not exists elements (
            id INTEGER PRIMARY KEY
        )')->query();

        ray()->enable();
        ray()->showQueries();
        ray()->showQueries();

        Yii::$app->db->createCommand('SELECT * FROM elements limit 1')->queryAll();

        ray()->stopShowingQueries();

        $this->assertCount(1, $this->client->sentPayloads());
    }

    /** @test */
    public function it_can_log_all_queries_in_a_callable()
    {
        Yii::$app->db->createCommand('CREATE TABLE if not exists elements (
            id INTEGER PRIMARY KEY
        )')->query();

        ray()->enable();
        ray()->showQueries(function () {
            // will be logged
            Yii::$app->db->createCommand('SELECT * FROM elements limit 1')->queryAll();
        });
        $this->assertCount(1, $this->client->sentPayloads());

        // will not be logged
        Yii::$app->db->createCommand('SELECT * FROM elements limit 1')->queryAll();
        $this->assertCount(1, $this->client->sentPayloads());
    }

    /** @test */
    public function it_can_replace_the_remote_path_with_the_local_one()
    {
        ray()->enable();

        Yii::$container->get(Settings::class)->remote_path = __DIR__;
        Yii::$container->get(Settings::class)->local_path = 'local_tests';

        ray('test');

        $this->assertStringContainsString(
            'local_tests',
            ArrayHelper::getValue($this->client->sentPayloads(), '0.payloads.0.origin.file')
        );
    }
}
