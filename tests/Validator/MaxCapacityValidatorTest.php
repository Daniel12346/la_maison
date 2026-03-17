<?php

/**
 * Testira MaxCapacity validator.
 *
 * Osigurava da privatne rezervacije ne ulaze u zajednički kapacitet od 20 mjesta
 * za regularne rezervacije u istom vremenskom slotu.
 */

namespace App\Tests\Validator;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Validator\MaxCapacity;
use App\Validator\MaxCapacityValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MaxCapacityValidatorTest extends ConstraintValidatorTestCase
{
    private ReservationRepository $repository;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->repository = $this->createMock(ReservationRepository::class);

        return new MaxCapacityValidator($this->repository);
    }

    private function buildReservation(int $partySize, bool $isPrivate): Reservation
    {
        //rezervacija se postavlja na najbliži petak kako bi se osiguralo da je datum uvijek u budućnosti i da su privatne rezervacije dozvoljene
        $date = new \DateTimeImmutable('today');
        $dayOfWeek = (int) $date->format('N'); // 5 = Petak
        $daysUntilFriday = ($dayOfWeek <= 5) ? 5 - $dayOfWeek : 12 - $dayOfWeek;
        $friday = $date->modify("+{$daysUntilFriday} days");

        $reservation = new Reservation();
        $reservation->setPartySize($partySize);
        $reservation->setDate(new \DateTime($friday->format('Y-m-d')));
        $reservation->setTimeSlot(new \DateTime('19:00:00'));
        $reservation->setIsPrivate($isPrivate);

        return $reservation;
    }

    /**
     * Obična rezervacija računa samo regularne rezervacije unutar slota.
     * Privatne rezervacije ne smiju smanjiti dostupni kapacitet od 20 mjesta.
     */
    public function testNormalReservationIgnoresPrivateGuestsForCapacity(): void
    {
        // Simuliramo situaciju u kojoj u slotu postoje samo privatni gosti,
        // pa zbroj regularnih gostiju ostaje 0.
        $this->repository
            ->method('sumPartySizeByDateAndTimeSlot')
            ->willReturn(0);

        $normalReservation = $this->buildReservation(15, false);

        $this->validator->validate($normalReservation, new MaxCapacity());

        $this->assertNoViolation();
    }

    /**
     * Privatna rezervacija mora biti valjana kada obična rezervacija već zauzima
     * isti vremenski slot, pod uvjetom da u tom slotu ne postoji druga privatna rezervacija.
     */
    public function testPrivateReservationIsValidWhenNormalAlreadyExists(): void
    {
        // U ovom vremenskom slotu ne postoji druga privatna rezervacija.
        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('sumPartySizeByDateAndTimeSlot');

        $privateReservation = $this->buildReservation(8, true);

        $this->validator->validate($privateReservation, new MaxCapacity());

        $this->assertNoViolation();
    }

    /**
     * Dvije privatne rezervacije u istom vremenskom slotu nisu dozvoljene.
     */
    public function testTwoPrivateReservationsInSameSlotNotAllowed(): void
    {
        // Simuliramo da već postoji privatna rezervacija u tom slotu.
        $this->repository
            ->method('findOneBy')
            ->willReturn(new Reservation());

        $privateReservation = $this->buildReservation(6, true);

        $this->validator->validate($privateReservation, new MaxCapacity());

        $this->buildViolation('A private reservation already exists for this date and time slot.')
            ->atPath('property.path.isPrivate')
            ->assertRaised();
    }

    /**
     * Rezervacija mora biti nevaljana kada zbroj postojećih gostiju i nove rezervacije
     * prelazi ukupni kapacitet od 20 mjesta.
     */
    public function testCapacityOverflowCreatesViolation(): void
    {
        // Postojećih 18 + nova 5 = 23 > 20, očekuje se poruka o preostalim mjestima.
        $this->repository
            ->method('sumPartySizeByDateAndTimeSlot')
            ->willReturn(18);

        $reservation = $this->buildReservation(5, false);

        $this->validator->validate($reservation, new MaxCapacity());

        $this->buildViolation('There are only {{ available }} seats left at the selected time.')
            ->setParameter('{{ available }}', '2')
            ->atPath('property.path.partySize')
            ->assertRaised();
    }
}
