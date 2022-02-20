<?php

namespace App\Event;

use Doctrine\Common\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestEventSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
          TestEvent::NAME => 'doSomething',
        ];
    }

    public function onTest(TestEvent $event) {
        dump($event);
        var_dump('kek');die;
    }

    public function doSomething() {
        dump('st');die;
    }
}