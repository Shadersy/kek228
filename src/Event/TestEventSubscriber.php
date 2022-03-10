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

    public function doSomething(TestEvent $event) {
        $response = array(
            'chat_id' => $event->getChatId(),
            'text' => $event->getMessage(),
        );

        $ch = curl_init('https://api.telegram.org/bot' . $event->getToken() . '/sendMessage');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_exec($ch);
        curl_close($ch);

    }
//
//    public function getTelgramApi($method, $options = null, $token) {
//        $str_request = 'https://api.telegram.org/bot' . $token . '/' . $method;
//
//        if ($str_request) {
//            $str_request .= '?' . http_build_query($options);
//        }
//        $request = file_get_contents($str_request);
//        return json_decode($request, 1);
//    }
//
//    public function setHook($set = 1) {
//        $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//        dump($this->getTelgramApi('setWebhook', ['url' => $set?$url:''], '5122155262:AAFarApJUef62TFh4uJ4nnFZ8O8ppu7Qoms'));
//        die;
//    }

}