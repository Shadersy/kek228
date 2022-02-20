<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TestEvent extends Event
{
    public const NAME = 'test.event';

    public function __construct()
    {

    }
}