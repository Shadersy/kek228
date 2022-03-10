<?php

namespace App\Entity;

use App\Repository\TelegramApiRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TelegramApiRepository::class)
 */
class TelegramApi
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $botToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $chatId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBotToken(): ?string
    {
        return $this->botToken;
    }

    public function setBotToken(?string $botToken): self
    {
        $this->botToken = $botToken;

        return $this;
    }

    public function getChatId(): ?string
    {
        return $this->chatId;
    }

    public function setChatId(?string $chatId): self
    {
        $this->chatId = $chatId;

        return $this;
    }
}
