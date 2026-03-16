<?php

namespace App\Tests\Controller;

use App\Controller\HomeController;
use App\Form\ReservationFormType;
use App\Repository\ReservationRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReservationFlowTest extends TestCase
{
    // Simulira cijeli proces rezervacije: od slanja forme, preko validacije, do preusmjeravanja na stranicu za potvrdu.
    public function testReservationSubmissionRedirectsToConfirmation(): void
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

        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
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
}
