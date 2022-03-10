<?php


namespace App\Controller;


use App\Entity\TelegramApi;
use App\Form\TelegramApiType;
use App\Repository\TelegramApiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TelegramApiController extends AbstractController
{

    /**
     * @Route("/telegram_configuration", name="telegram_configuration", methods={"GET", "POST"} )
     */
    public function index(Request $request, TelegramApiRepository $repository) {

        $existedConfig = $repository->findAll();
        if($existedConfig) {
            $existedTelegramApi = $existedConfig[0];
            $form = $this->createForm(TelegramApiType::class, $existedTelegramApi);
        } else {
            $telegramApi = new TelegramApi();
            $form = $this->createForm(TelegramApiType::class, $telegramApi);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            if($existedConfig) {
                $existedTelegramApi->setBotToken($form->getData()->getBottoken());
                $existedTelegramApi->setChatId($form->getData()->getChatId());
            } else {
                $telegramApiConnector = new TelegramApi();
                $telegramApiConnector->setBotToken($form->getData()->getBottoken());
                $telegramApiConnector->setChatId($form->getData()->getChatId());

                $em->persist($telegramApiConnector);
            }
            $em->flush();
        }

            return $this->render('telegram/telegram.html.twig', [
            'form' => $form->createView(),
            'user' => $this->getUser()
        ]);
    }
}