<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\File;
use App\Entity\Ticket;
use App\Event\MailEvent;
use App\Event\MailEventSubsriber;
use App\Event\TelegramEvent;
use App\Event\TelegramEventSubscriber;
use App\Form\CommentType;
use App\Form\TicketType;
use App\Repository\TelegramApiRepository;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\String\Slugger\SluggerInterface;



/**
 * @Route("/ticket")
 */
class TicketController extends AbstractController
{
    private $tokenStorage;

    public static $STATUS_V_OBRABOTKE = '1';
    public static $STATUS_CANCELED = '2';
    public static $STATUS_CLOSED = '3';
    public static $STATUS_IN_JOB = '4';

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    /**
     * @Security("is_authenticated()")
     * @Route("/", name="ticket_index", methods={"GET", "POST"})
     */
    public function index(
        TicketRepository $ticketRepository,
        EventDispatcherInterface $eventDispatcher,
        UserRepository $userRepository,
        Request $request
    ): Response
    {
        $builder = $ticketRepository->createQueryBuilder('p');

        $builder = $ticketRepository->createQueryBuilder('p');
        if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {



        } else {
            $builder->andWhere('p.sender = ' . $this->getUser()->getId());
        }

        if ($request->request->get('filter') && $request->request->get('filter')['number'] != '') {
            $filter = $request->request->get('filter');

            $builder->where('p.id =' .  $filter['number']);
        }

        if ($request->request->get('filter')) {
            if(isset($request->request->get('filter')['status'])) {
                $filter = $request->request->get('filter');

                switch ($filter['status']) {
                    case self::$STATUS_CLOSED :
                        $builder->andWhere('p.status in (\'Закрыт\')');
                        break;
                    case self::$STATUS_CANCELED :
                        $builder->andWhere('p.status in (\'Отклонено\')');
                        break;
                    case self::$STATUS_V_OBRABOTKE :
                        $builder->andWhere('p.status in (\'В обработке\')');
                        break;
                    case self::$STATUS_IN_JOB :
                        $builder->andWhere('p.status in (\'В работе\')');
                        break;
                }
            }

            if (isset($request->request->get('filter')['author'])) {
                $filter = $request->request->get('filter');

                $author = $userRepository->findOneBy(['login' => $filter['author']]);
                if($author)
                $builder->andWhere('p.sender = \'' . $author->getId() . '\'' );
            }

            if (isset($request->request->get('filter')['priority'])) {
                $filter = $request->request->get('filter');

//                dump($filter['priority']);die;
                $builder->andWhere('p.importance in (\'' . $filter['priority']  . '\')');
//                dump($builder->getQuery());die;
            }
        }



        $tickets = $builder->getQuery()->execute();

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/new", name="ticket_new", methods={"GET","POST"})
     * @Security("is_authenticated()")
     */
    public function new(Request $request, AuthorizationCheckerInterface $authChecker, TelegramApiRepository $telegramApiRepository,
                        UserRepository $userRepository, EventDispatcherInterface $dispatcher): Response
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

            $existedTelegramConfig = $telegramApiRepository->findAll();

            if($existedTelegramConfig) {
                $telegramConfig = $existedTelegramConfig[0];
                $priority = null;

                switch ($ticket->getImportance()) {
                    case 1 :
                        $priority = 'Низкий';
                        break;
                    case 2 :
                        $priority = 'Средний';
                        break;
                    case 3 :
                        $priority = 'Высокий';
                        break;
                }

                $deadLine = $ticket->getDeadline();
                if ($deadLine) {
                    $deadLine = $deadLine->format('Y-m-d');
                } else {
                    $deadLine = 'Не указан';
                }

                $event = new TelegramEvent(
                    $telegramConfig->getBotToken(),
                    $telegramConfig->getChatId(),
                    '
                    В системе создана заявка №' . $ticket->getId() . ' ' . PHP_EOL .
                    'Приоритет: ' . $priority . ' ' . PHP_EOL .
                    'Автор: ' . $ticket->getSender() . ' ' . PHP_EOL .
                    'Дата формирования: ' . $ticket->getCreatedOn()->format('Y:m:d') . ' ' . PHP_EOL .
                    'Срок: ' . $deadLine
                );
                $dispatcher->addSubscriber(new TelegramEventSubscriber());
                $dispatcher->dispatch($event, TelegramEvent::NAME);
            }

            #TODO: Доделать майлер
//            $dispatcher->addSubscriber(new MailEventSubsriber());
//            $dispatcher->dispatch(new MailEvent(), MailEvent::NAME);
            return $this->redirectToRoute('ticket_index');

        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_show", methods={"GET", "POST"})
     * @Security("is_authenticated()")
     */
    public function show(Ticket $ticket, Request $request, UserRepository $userRepository,  SluggerInterface $slugger): Response
    {

        $em = $this->getDoctrine()->getManager();
        $em->find(Ticket::class, $ticket->getId());



        if ($this->getUser()->getLogin() != $ticket->getSender()->getLogin() && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('ticket_index');
        }

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
