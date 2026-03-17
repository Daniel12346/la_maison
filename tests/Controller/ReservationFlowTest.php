<?php

namespace App\Tests\Controller;

use App\Controller\HomeController;
use App\Form\ReservationFormType;
use App\Repository\ReservationRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ReservationFlowTest extends TestCase
{
    // Simulira neispravno slanje forme i provjerava da se stranica ponovno prikazuje s greškama.
    public function testInvalidReservationSubmissionRendersHomeWithErrors(): void
    {
        $reservationRepository = $this->createMock(ReservationRepository::class);
        $reservationRepository->method('getAvailableTimeSlots')
            ->willReturn(['12:00']);

        $csrfTokenManager = new class implements CsrfTokenManagerInterface {
            public function getToken(string $tokenId): CsrfToken
            {
                return new CsrfToken($tokenId, 'test-csrf-token');
            }

            public function refreshToken(string $tokenId): CsrfToken
            {
                return new CsrfToken($tokenId, 'test-csrf-token');
            }

            public function removeToken(string $tokenId): ?string
            {
                return null;
            }

            public function isTokenValid(CsrfToken $token): bool
            {
                return 'test-csrf-token' === $token->getValue();
            }
        };

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new CsrfExtension($csrfTokenManager))
            ->addType(new ReservationFormType($reservationRepository))
            ->getFormFactory();

        $form = $formFactory->create(ReservationFormType::class);
        $formName = $form->getName();
        $csrfToken = null;
        if ($form->has('_token')) {
            $csrfToken = $form->get('_token')->getData();
        }

        $router = $this->createMock(UrlGeneratorInterface::class);

        $twig = $this->createMock(\Twig\Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                'home/index.html.twig',
                $this->callback(static function (array $context): bool {
                    return isset($context['controller_name'], $context['reservationForm'])
                        && 'HomeController' === $context['controller_name'];
                })
            )
            ->willReturn('rendered-invalid');

        $container = new Container();
        $container->set('form.factory', $formFactory);
        $container->set('router', $router);
        $container->set('twig', $twig);

        $controller = new HomeController();
        $controller->setContainer($container);

        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($entityManager);

        $tomorrow = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');

        // Neispravno: korisnik je poslao formu prerano, bez odabranog timeSlota.
        $formValues = [
            'partySize' => 1,
            'date' => $tomorrow,
            'isPrivate' => 0,
        ];

        if ($csrfToken !== null) {
            $formValues['_token'] = $csrfToken;
        }

        $request = Request::create('/reserve', 'POST', [
            $formName => $formValues,
        ]);

        $response = $controller->submit($request, $doctrine);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame('rendered-invalid', $response->getContent());
    }
}
