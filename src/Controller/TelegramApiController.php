<?php


namespace App\Controller;


use App\Entity\TelegramApi;
use App\Form\TelegramApiType;
use App\Repository\TelegramApiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class TelegramApiController extends AbstractController
{

    /**
     * @IsGranted("ROLE_ADMIN")
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
                $existedTelegramApi
                    ->setBotToken($form->getData()->getBottoken())
                    ->setChatId($form->getData()->getChatId());
            } else {
                $em->persist(
                (new TelegramApi())
                    ->setBotToken($form->getData()->getBottoken())
                    ->setChatId($form->getData()->getChatId())
                );
            }
            $em->flush();
        }

            return $this->render('telegram/telegram.html.twig', [
            'form' => $form->createView(),
            'user' => $this->getUser()
        ]);
    }
}