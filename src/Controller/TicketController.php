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
use App\Service\CommentService;
use App\Service\TicketService;
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
    private TicketService $ticketService;
    private CommentService $commentService;

    public const STATUS_NEW = '1';
    public const STATUS_CANCELED = '2';
    public const STATUS_CLOSED = '3';
    public const STATUS_IN_JOB = '4';

    public $statusLabels = [
        self::STATUS_NEW => 'В обработке',
        self::STATUS_IN_JOB => 'В работе',
        self::STATUS_CANCELED => 'Отклонено',
        self::STATUS_CLOSED => 'Закрыт',
    ];

    public const PRIORITY_LOW = 'Низкий';
    public const PRIORITY_MIDDLE = 'Средний';
    public const PRIORITY_HIGH = 'Высокий';

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TicketService  $ticketService,
        CommentService $commentService
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->ticketService = $ticketService;
        $this->commentService = $commentService;
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
                    case self::STATUS_CLOSED :
                        $builder->andWhere('p.status in (\'Закрыт\')');
                        break;
                    case self::STATUS_CANCELED :
                        $builder->andWhere('p.status in (\'Отклонено\')');
                        break;
                    case self::STATUS_NEW :
                        $builder->andWhere('p.status in (\'В обработке\')');
                        break;
                    case self::STATUS_IN_JOB :
                        $builder->andWhere('p.status in (\'В работе\')');
                        break;
                }
            }

            if (isset($request->request->get('filter')['author'])) {
                $filter = $request->request->get('filter');
                $author = $userRepository->findOneBy(['login' => $filter['author']]);

                if ($author) {
                    $builder->andWhere('p.sender = \'' . $author->getId() . '\'');
                }
            }

            if (isset($request->request->get('filter')['priority'])) {
                $filter = $request->request->get('filter');

                $builder->andWhere('p.importance in (\'' . $filter['priority']  . '\')');
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
        $filledTicket = new Ticket();
        $form = $this->createForm(TicketType::class, $filledTicket);

        $form->handleRequest($request);

        $user = $this->getUser();
        $user = $userRepository->find($user->getId());

        if ($form->isSubmitted() && $form->isValid()) {

            $ticket = $this->ticketService
                ->newTicket(
                    $user,
                    $filledTicket->getImportance(),
                    $filledTicket->getDescription(),
                    $filledTicket->getDeadline()
                );

            $existedTelegramConfig = $telegramApiRepository->findAll();

            if ($existedTelegramConfig) {
                $telegramConfig = $existedTelegramConfig[0];
                $priority = null;

                switch ($ticket->getImportance()) {
                    case 1 :
                        $priority = self::PRIORITY_LOW;
                        break;
                    case 2 :
                        $priority = self::PRIORITY_MIDDLE;
                        break;
                    case 3 :
                        $priority = self::PRIORITY_HIGH;
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

        unset($this->statusLabels[$ticket->getStatus()]);

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory();

        $formStatus = $formFactory->createBuilder()
            ->add('Status', ChoiceType::class, [
                'choices' => $this->statusLabels,
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

            $systemUser = $userRepository->findOneBy(['login' => 'system']);
            $this->commentService->newComment($systemUser, $ticket, $selectedStatus);

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        $filledComment = new Comment();
        $form = $this->createForm(CommentType::class, $filledComment);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $comment = $this
                ->commentService
                ->newComment($user, $ticket, $filledComment->getMessage());


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
                   throw $e;
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

    /**
     * @Route("/download_file/{fileName}", name="file_download", methods={"GET"})
     * @Security("is_authenticated()")
     */
    public function downloadFile(Request $request, File $file) {
        $content = file_get_contents($this->getParameter('upload_directory') . $file->getFileName());

        $response = new Response();
        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$file->getFileName());

        $response->setContent($content);
        return $response;
    }
}
