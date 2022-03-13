<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

class MailEvent extends Event
{
    public const NAME = 'mail.event';

}