<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TestEvent extends Event
{
    public const NAME = 'test.event';

    private $token;
    private $chatId;
    private $message;

    public function __construct(string $token, string $chatId, string $message)
    {
        $this->token = $token;
        $this->chatId = $chatId;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getChatId(): string
    {
        return $this->chatId;
    }

    /**
     * @param string $chatId
     */
    public function setChatId(string $chatId): void
    {
        $this->chatId = $chatId;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}