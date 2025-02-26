<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TicketRepository;
use App\Service\ExcelService;
use App\Service\TicketService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ExcelController extends AbstractController
{
    private ExcelService $excelService;
    private TicketService $ticketService;

    public function __construct(
        ExcelService $excelService,
        TicketService $ticketService
    ) {
        $this->excelService = $excelService;
        $this->ticketService = $ticketService;
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/generate_report", name="excel_report", methods={"GET", "POST" })
     */
    public function generateExcel(Request $request, TicketRepository $repository) {

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory();

        $form = $formFactory->createBuilder()
                ->add('created_from', DateType::class,
                    [
                        'required' => true,
                        'widget' => 'single_text',
                        'label' => 'От:',
                        'placeholder' => [
                            'year' => 'Год', 'month' => 'Месяц', 'day' => 'День',
                        ],
                    ])
                ->add('created_to', DateType::class,
                    [
                        'required' => true,
                        'widget' => 'single_text',
                        'label' => 'До',
                        'placeholder' => [
                            'year' => 'Год', 'month' => 'Месяц', 'day' => 'День',
                        ],
                    ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

           $tickets = $this->ticketService->getTickets($form);

            if (empty($tickets)) {
                return $this->render('excel/report_excel.html.twig', [
                    'ticketNotFound' => true,
                    'user' => $this->getUser(),
                    'form' => $form->createView(),
                ]);
            }

            $fileName = 'report' . 'kek' . '.xlsx' ?? 'project.xlsx';

            $excelFile = $this->excelService->generateExcelReport($form, $tickets, $fileName);

            return $this->file($excelFile, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
        }

        return $this->render('excel/report_excel.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

}