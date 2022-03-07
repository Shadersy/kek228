<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\File;
use App\Entity\Ticket;
use App\Event\TestEvent;
use App\Event\TestEventSubscriber;
use App\Form\CommentType;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Security\User;
use App\Security\UserAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;


/**
 * @Route("/ticket")
 */
class TicketController extends AbstractController
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    /**
     * @Route("/", name="ticket_index", methods={"GET"})
     */
    public function index(TicketRepository $ticketRepository, EventDispatcherInterface $eventDispatcher,
                          Security $security, UserRepository $userRepository): Response
    {

//        if ($this->tokenStorage->getToken()->getUser() == 'anon.') {
//            return $this->render('course/index.html.twig', [
//                'courses' => $ticketRepository->findAll()
//            ]);
//        }
//
//        $event = new TestEvent();
//        $eventDispatcher->addSubscriber(new TestEventSubscriber());
//        $eventDispatcher->dispatch($event, TestEvent::NAME);

//        $token = $this->tokenStorage->getToken()->getUser()->getApiToken();

        if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {

            $tickets = $ticketRepository->findAll();

        } else {

            $tickets = $ticketRepository->findBy(['sender' => $this->getUser()->getId()]);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/new", name="ticket_new", methods={"GET","POST"})
     */
    public function new(Request $request, AuthorizationCheckerInterface $authChecker, UserRepository $userRepository): Response
    {
//        if (false === $authChecker->isGranted('ROLE_SUPER_ADMIN')) {
//            return $this->redirectToRoute('course_index');
//        }

        $filledTicket = new Ticket();
        $form = $this->createForm(TicketType::class, $filledTicket);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        $user = $userRepository->find($user->getId());

        if ($form->isSubmitted() && $form->isValid()) {

            $ticket = new Ticket();
            $ticket->setCreatedOn(new \DateTime());
            $ticket->setImportance($filledTicket->getImportance());
            $ticket->setStatus('В обработке');
            $ticket->setSender($user);
            $ticket->setDescription($filledTicket->getDescription());

                if ($filledTicket->getDeadline()) {
                    $ticket->setDeadline($filledTicket->getDeadline());
                }

            $em->persist($ticket);
            $em->flush();

            return $this->redirectToRoute('ticket_index');

        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_show", methods={"GET", "POST"})
     */
    public function show(Ticket $ticket, Request $request, UserRepository $userRepository,  SluggerInterface $slugger): Response
    {

        $em = $this->getDoctrine()->getManager();
        $em->find(Ticket::class, $ticket->getId());

        $user = $this->getUser();

        $statuses = [
            'В обработке' => 'В обработке',
            'В работе' => 'В работе',
            'Отклонено' => 'Отклонено',
            'Закрыт' => 'Закрыт',
        ];

        unset($statuses[$ticket->getStatus()]);

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory();


        $formStatus = $formFactory->createBuilder()
            ->add('Status', ChoiceType::class, [
                'choices' => $statuses,
                'required' => true,
                'label' => 'Статус задачи'])
            ->add('Filter', SubmitType::class, [
                'label' => 'Подтвердить',
                'attr' => ['style' => 'margin-top: 2%'],
            ])
            ->getForm();


        $formStatus->handleRequest($request);

        if ($formStatus->isSubmitted() && $formStatus->isValid()) {
            $selectedStatus = $formStatus->getData()['Status'];
            $ticket->setStatus($selectedStatus);

            $userSystem = $userRepository->findOneBy(['login' => 'system']);
            $comment = new Comment();
            $comment->setSender($userSystem);
            $comment->setCreatedOn(new \DateTime());
            $comment->setTicket($ticket);
            $comment->setMessage($user->getUsername().  ' изменил статус заявки на "' . $selectedStatus . '"');
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        $filledComment = new Comment();
        $form = $this->createForm(CommentType::class, $filledComment);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $comment = new Comment();
            $comment->setSender($user);
            $comment->setCreatedOn(new \DateTime());
            $comment->setTicket($ticket);
            $comment->setMessage($filledComment->getMessage());
            $file = $form->get('file')->getNormData();

            if ($file) {

                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'. $file->guessExtension();
                $extension = $file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );

                    $fileObject = new File();
                    $fileObject->setFileName($newFilename);
                    $fileObject->setExtension($extension);
                    $em->persist($fileObject);
                    $comment->addFile($fileObject);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

            }

            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/show.html.twig', [
            'user' => $user->getUsername(),
            'ticket' => $ticket,
            'form' => $form->createView(),
            'formStatus' => $formStatus->createView(),
            'upload_directory' => $this->getParameter('upload_directory'),
        ]);
    }
}
