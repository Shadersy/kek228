<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\UserDeprecated;
use App\Form\TicketType;
use App\Form\UserCreateType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/admin")
 */
class UserController extends AbstractController
{
    /**
     *
     * @Route("/user_create", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserRepository $userRepository): Response
    {
        $hasAccess = $this->isGranted('ROLE_ADMIN');
        dump($hasAccess);
        dump($this->getUser()->getRoles()); die;
        $filledUser = new User();

        $form = $this->createForm(UserCreateType::class, $filledUser);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $formUser = $form->getData();

            $existedUser = $userRepository->findOneBy(['login' => $formUser->getLogin()]);

            if ($existedUser) {
                $error = 'Пользователь с таким именем существует';

                return $this->render('user/usercreate.html.twig',
                    [
                        'form' => $form->createView(),
                        'error' => $error,
                    ]);

            } else {

                $user = new UserDeprecated();
                $user->setLogin($formUser->getLogin());
                $user->setRoles(['ROLE_USER']);

                $passwordHasherFactory = new PasswordHasherFactory([
                    UserDeprecated::class => ['algorithm' => 'auto'],
                    PasswordAuthenticatedUserInterface::class => [
                        'algorithm' => 'auto',
                        'cost' => 15,
                    ],
                ]);

                $passwordHasher = new UserPasswordHasher($passwordHasherFactory);
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $formUser->getPassword()
                );

                $user->setPassword($hashedPassword);
                $user->setPassword($hashedPassword);
                $em->persist($user);
                $em->flush();

            }
        }

        return $this->render('user/usercreate.html.twig',
         [
             'form' => $form->createView(),

         ]);

    }
}