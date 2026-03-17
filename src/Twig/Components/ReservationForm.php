<?php

namespace App\Twig\Components;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Form\ReservationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent]
final class ReservationForm extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ReservationFormType::class);
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        $this->submitForm();

        /** @var Reservation $reservation */
        $reservation = $this->getForm()->getData();

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return $this->redirectToRoute('confirmation', [
            'referenceCode' => $reservation->getReferenceCode(),
        ]);
    }
}
