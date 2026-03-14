<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/reserve', name: 'reservation_submit', methods: ['POST'])]
    public function submit(Request $request, ManagerRegistry $doctrine): Response
    {
        //stvara se novi form koji je različit od onog u ReservationForm komponenti ali prima iste podatke preko POST zahtjeva iz te komponente
        //form kao liveComponent je koristan za dinamičko prikazivanje polja, ali iz njega se ne može direktno obaviti preusmjeravanje nakon podnošenja forme, pa se zato koristi ovaj endpoint
        $form = $this->createForm(ReservationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Reservation $reservation */
            $reservation = $form->getData();
            $em = $doctrine->getManager();
            $em->persist($reservation);
            $em->flush();
            return $this->redirectToRoute('confirmation', ['referenceCode' => $reservation->getReferenceCode()]);
        }

        return $this->redirectToRoute('app_home');
    }
}
