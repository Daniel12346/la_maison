<?php

namespace App\Controller;

use App\Entity\Reservation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConfirmationController extends AbstractController
{
    #[Route('/confirmation/{id}', name: 'confirmation')]
    public function index(Reservation $reservation): Response
    {
        $confirmationNumber = 'LM-' . strtoupper(substr(md5(uniqid()), 0, 5));
        return $this->render('confirmation/index.html.twig', [
            'controller_name' => 'ConfirmationController',
            'reservation' => $reservation,
            'confirmation_number' => $confirmationNumber,
        ]);
    }
}
