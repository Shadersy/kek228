<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class RegistrationController extends AbstractController
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     *
     * @Route("/registration", name="registration" )
     */
    public function index(Request $request, UserRepository $repository)
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
            $user->setRoles(['ROLE_USER']);

            $em = $this->getDoctrine()->getManager();

            $alreadyExistedUser = $repository->findOneBy(['login' => $user->getLogin()]);

            $error = null;

            if(!$alreadyExistedUser) {
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('ticket_index');
            } else {
                $error = 'Пользователь уже существует';

                return $this->render('registration/index.html.twig', [
                    'form' => $form->createView(),
                    'user' => $this->getUser(),
                    'error' => $error,
                ]);
            }
        }

        return $this->render('registration/index.html.twig', [
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ]);
    }
}
