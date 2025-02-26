<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\TicketRepository;
use Cassandra\Collection;

class TicketService
{
    private TicketRepository $ticketRepo;

    public function __construct(TicketRepository $ticketRepo)
    {
        $this->ticketRepo = $ticketRepo;
    }

    public function newTicket(
        User $user,
        string $importance,
        string $description = '',
        ?\DateTimeInterface $deadLine
    ): Ticket {
        $ticket = new Ticket();
        $ticket
            ->setCreatedOn(new \DateTime())
            ->setImportance($importance)
            ->setStatus('В обработке')
            ->setSender($user)
            ->setDescription($description)
            ->setDeadline($deadLine);

        $em = $this->getDoctrine()->getManager();
        $em->persist($ticket);
        $em->flush();

        return $ticket;
    }

    public function getTickets(Forms $form): Collection {
        $createdFrom = $form->getData()['created_from']->format('Y-m-d');
        $createdTo = $form->getData()['created_to']->format('Y-m-d');

        $builder = $this->ticketRepo
            ->createQueryBuilder('t')
            ->andWhere('t.created_on >= ' . '\'' . $createdFrom . '\'' . ' AND t.created_on < ' . '\'' . $createdTo . '\'');

        $tickets = $builder->getQuery()->getResult();

        return $tickets;
    }
}