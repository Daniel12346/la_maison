<?php

// Ovaj test provjerava sljedeće:
// 1) Metoda getAvailableTimeSlots() ne vraća termin koji je već popunjen (npr. 19:00).
// 2) Otkazane rezervacije (status 'Cancelled') se ne računaju kao popunjeni slot (ni za regularne ni za privatne rezervacije).
//
// Test koristi mockove za Doctrine Query i EntityManager kako bi se mogao pokrenuti bez povezanosti s bazom podataka.

namespace App\Tests\Repository;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

class ReservationRepositoryTest extends TestCase
{
    public function testGetAvailableTimeSlotsOmitsFilledSlot(): void
    {
        $date = new \DateTime('tomorrow');
        $partySize = 5;

        // Simulira rezervaciju koja popunjava termin u 19:00
        $filledTimeSlot = new \DateTime('19:00:00');
        $queryResult = [
            ['timeSlot' => $filledTimeSlot],
        ];

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResult);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('createQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder(ReservationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager'])
            ->getMock();
        $repository->method('getEntityManager')->willReturn($entityManager);

        $available = $repository->getAvailableTimeSlots($date, $partySize, false);

        $this->assertIsArray($available);
        $this->assertNotContains('19:00', $available, 'Filled time slot should not be available.');
    }

    public function testCancelledReservationsDoNotFillSlot(): void
    {
        $date = new \DateTime('tomorrow');
        $partySize = 1;

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('setParameter')
            ->willReturnSelf();
        $query->expects($this->exactly(2))
            ->method('getResult')
            ->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
            ->method('createQuery')
            ->with($this->callback(function (string $dql) {
                // Osigura da se u DQL upitu filtriraju otkazane rezervacije
                return str_contains($dql, "r.status != 'Cancelled'");
            }))
            ->willReturn($query);

        $repository = $this->getMockBuilder(ReservationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager'])
            ->getMock();
        $repository->method('getEntityManager')->willReturn($entityManager);

        $available = $repository->getAvailableTimeSlots($date, $partySize, false);

        $this->assertContains('19:00', $available, 'Cancelled reservation should not make the slot full.');

        $availablePrivate = $repository->getAvailableTimeSlots($date, $partySize, true);
        $this->assertContains('19:00', $availablePrivate, 'Cancelled private reservation should not block the slot.');
    }
}
