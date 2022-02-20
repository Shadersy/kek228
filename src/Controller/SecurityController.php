<?php


namespace App\Controller;


use App\Entity\User;
use App\Entity\UserDeprecated;
use App\Form\AuthorizationFormType;
use App\Repository\UserRepository;
use Captcha\Bundle\CaptchaBundle\Controller\CaptchaHandlerController;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends AbstractController
{
    private $entityManager;
    private $tokenStorage;
    private $session;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em,
        SessionInterface $session
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $em;
        $this->session = $session;
    }

    /**
     * @param Request $request
     *
     * @Route("/login", name="app_login", methods={"GET", "POST"})
     */
    public function loginAction(Request $request, UserRepository $userRepository, AuthorizationCheckerInterface $authChecker): Response
    {

//        if ($authChecker->isGranted('ROLE_USER')) {
//            return $this->redirectToRoute('ticket_index');
//        }
//
//        $user = new User();
//
//        $form = $this->createForm(AuthorizationFormType::class, $user);
//
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//
//
//            $authenticatedUser = $userRepository->findOneBy(['login' => $user->getLogin(), 'password' => $user->getPassword()]);
//
//            if (!$authenticatedUser) {
//                return $this->render('security/register.html.twig', [
//                    'authorizationForm' => $form->createView(),
//                    'error' => 'Данные не верные'
//                ]);
//            }

//            $token = new UsernamePasswordToken($authenticatedUser->getLogin(), null, 'main', $user->getRoles());
//
//            $this->tokenStorage->setToken($token);
//
//            $this->session->set('_security_main', serialize($token));
//            $this->session->save();


//            dump($this->getUser());die;
            return $this->redirectToRoute('ticket_index');
//        }

//        return $this->render('security/register.html.twig', [
////            'authorizationForm' => $form->createView(),
//        ]);
    }

    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}