<?php


namespace App\Event;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailEventSubsriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            MailEvent::NAME => 'send',
        ];
    }

    public function send(MailEvent $event, MailerInterface $mailer)
    {
 //TODO: Доделать майлер
        $email = (new Email())
            ->from('hello@example.com')
            ->to('shadersy@mail.ru')
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $mailer->send($email);
    }
}