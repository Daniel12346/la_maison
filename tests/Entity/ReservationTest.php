<?php

// Ovaj test provjerava da rezervacija s datumom koji je više od 30 dana u budućnosti
// ne prolazi validaciju i da se vraća odgovarajuća poruka o pogrešnom rasponu datuma.

namespace App\Tests\Entity;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Validator\MaxCapacity;
use App\Validator\MaxCapacityValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Validation;

class ReservationTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testReservationDateMoreThan30DaysIsInvalid(): void
    {
        $repository = $this->createMock(ReservationRepository::class);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory(new class($repository) implements ConstraintValidatorFactoryInterface {
                private ReservationRepository $repository;
                private ConstraintValidatorFactory $inner;

                public function __construct(ReservationRepository $repository)
                {
                    $this->repository = $repository;
                    $this->inner = new ConstraintValidatorFactory();
                }

                public function getInstance(Constraint $constraint): ConstraintValidatorInterface
                {
                    if ($constraint instanceof MaxCapacity) {
                        return new MaxCapacityValidator($this->repository);
                    }

                    return $this->inner->getInstance($constraint);
                }
            })
            ->getValidator();

        $reservation = new Reservation();
        $reservation->setDate((new \DateTime())->modify('+31 days'));
        $reservation->setPartySize(2);
        $reservation->setFullName('Test User');
        $reservation->setEmail('test@example.com');
        $reservation->setTimeSlot(new \DateTime('12:00:00'));
        $reservation->setPhone('+123456789');
        $reservation->setIsPrivate(false);

        $violations = $validator->validate($reservation);

        $this->assertNotEmpty($violations);
        $this->assertStringContainsString(
            'Please select a date between today and 30 days from now.',
            (string) $violations
        );
    }
}
