<?php

namespace App\Tests\Controller;

use App\Controller\ConfirmationController;
use App\Controller\HomeController;
use App\Entity\Reservation;
use App\Form\ReservationFormType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReservationControllerTest extends TestCase
{
    // Ovaj test provjerava da polje forme za rezervaciju može biti poslano,
    // da se na kraju poziva metoda submit kontrolera i da se korisnik preusmjerava
    // na stranicu za potvrdu rezervacije.
    public function testSubmitRedirectsToConfirmation(): void
    {
        $reservationRepository = $this->createMock(ReservationRepository::class);
        $reservationRepository->method('getAvailableTimeSlots')
            ->willReturn(['12:00']);

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addType(new ReservationFormType($reservationRepository))
            ->getFormFactory();

        $form = $formFactory->create(ReservationFormType::class);
        $formName = $form->getName();
        $csrfToken = null;
        if ($form->has('_token')) {
            $csrfToken = $form->get('_token')->getData();
        }

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')
            ->willReturn('/confirmation/REF123');

        $container = new Container();
        $container->set('form.factory', $formFactory);
        $container->set('router', $router);

        $controller = new HomeController();
        $controller->setContainer($container);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function () {});
        $entityManager->method('flush')->willReturnCallback(function () {});

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($entityManager);

        $tomorrow = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');

        $formValues = [
            'partySize' => 1,
            'date' => $tomorrow,
            'isPrivate' => 0,
            'timeSlot' => '12:00',
            'fullName' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+123456789',
        ];

        if ($csrfToken !== null) {
            $formValues['_token'] = $csrfToken;
        }

        $request = Request::create('/reserve', 'POST', [
            $formName => $formValues,
        ]);

        $response = $controller->submit($request, $doctrine);

        $this->assertSame(302, $response->getStatusCode());
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('/confirmation/', $location);
    }

    // Ovaj test provjerava da ConfirmationController pravilno prikazuje
    // detalje rezervacije (ime, datum, vrijeme itd.) u predlošku.
    public function testConfirmationPageShowsReservationData(): void
    {
        $reservation = new Reservation();
        $reservation->setFullName('Jane Doe');
        $reservation->setEmail('jane@example.com');
        $reservation->setPhone('+123456789');
        $reservation->setDate(new \DateTime('2026-03-20'));
        $reservation->setTimeSlot(new \DateTime('12:00:00'));
        $reservation->setPartySize(4);
        $reservation->setReferenceCode('ABC123');

        $twig = $this->createMock(\Twig\Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                'confirmation/index.html.twig',
                $this->callback(function (array $context) use ($reservation) {
                    return isset($context['reservation'])
                        && $context['reservation']->getFullName() === $reservation->getFullName()
                        && $context['reservation']->getReferenceCode() === $reservation->getReferenceCode();
                })
            )
            ->willReturn('rendered');

        $container = new Container();
        $container->set('twig', $twig);

        $controller = new ConfirmationController();
        $controller->setContainer($container);

        $response = $controller->index($reservation);

        $this->assertSame('rendered', $response->getContent());
    }
}
