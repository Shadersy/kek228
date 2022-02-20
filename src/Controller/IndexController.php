<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


class IndexController extends AbstractController
{
    /**
     * @Route("/new_user", name="main_ticket_index", methods={"GET"})
     */
    public function index(AuthorizationCheckerInterface $authChecker): Response
    {

        if ($authChecker->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('ticket_index');
        }

        return  $this->redirectToRoute('app_login');
    }
}
