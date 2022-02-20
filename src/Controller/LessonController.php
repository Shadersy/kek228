<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\Course;
use App\Form\LessonType;
use App\Repository\LessonRepository;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/lesson")
 */
class LessonController extends AbstractController
{


    private $bilingService;
    private $tokenStorage;

    public function __construct(BillingClient $billingService, TokenStorageInterface $tokenStorage)
    {
        $this->bilingService = $billingService;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/new", name="lesson_new", methods={"GET","POST"})
     */
    public function new(Request $request, AuthorizationCheckerInterface $authChecker): Response
    {

        if (false === $authChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('course_index');
        }

        $lesson = new Lesson();
        $courseId = $request->query->get('id');
        $course = $this->getDoctrine()->getRepository(Course::class)->find($courseId);


        $builder = $this->createFormBuilder($lesson);
        $lessonType = new LessonType();
        $lessonType->buildForm($builder, ['empty_data' => $course]);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($lesson);
            $entityManager->flush();

            return $this->redirectToRoute('course_show', ['id' => $courseId]);
        }

        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="lesson_show", methods={"GET"})
     */
    public function show(Lesson $lesson, AuthorizationCheckerInterface $authChecker): Response
    {
        if ($this->tokenStorage->getToken()->getUser() == 'anon.') {
            throw new AccessDeniedException();
        }

        $token = $this->tokenStorage->getToken()->getUser()->getApiToken();

        $courseAvailable = json_decode($this->bilingService->checkAvailableCourse(
            $token,
            $lesson->getCourse()->getCode()
        ));



        if (!$courseAvailable) {
            throw new AccessDeniedException();
        }

        $course = $lesson->getCourse();
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'course' => $course,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="lesson_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Lesson $lesson, AuthorizationCheckerInterface $authChecker): Response
    {
        if (false === $authChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('course_index');
        }


        $builder = $this->createFormBuilder($lesson);

        $lessonType = new LessonType();
        $lessonType->buildFormWithoutCourse($builder);

        $form = $builder->getForm();


        $form->handleRequest($request);
        $course = $lesson->getCourse();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('course_show', ['id' => $lesson->getCourse()->getId()]);
        }

        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="lesson_delete", methods={"POST"})
     */
    public function delete(Request $request, Lesson $lesson, AuthorizationCheckerInterface $authChecker): Response
    {
        if (false === $authChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('course_index');
        }

        if ($this->isCsrfTokenValid('delete' . $lesson->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($lesson);
            $entityManager->flush();
        }

        return $this->redirectToRoute('course_show', ['id' => $lesson->getCourse()->getId()]);
    }
}
