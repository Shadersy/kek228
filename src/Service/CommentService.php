<?php

namespace App\Service;

use App\Entity\Comment;

class CommentService
{

    public function newComment(User $user, Ticket $ticket, string $selectedStatus): Comment {
        $comment = new Comment();
        $comment
            ->setSender($user)
            ->setCreatedOn(new \DateTime())
            ->setTicket($ticket)
            ->setMessage($user->getUsername().  ' изменил статус заявки на "' . $selectedStatus . '"');

        $em = $this->getDoctrine()->getManager();
        $em->persist($comment);
        $em->flush();

        return $comment;
    }
}