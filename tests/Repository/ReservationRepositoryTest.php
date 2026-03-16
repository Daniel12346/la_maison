<?php

// Ovaj test provjerava da metoda getAvailableTimeSlots() ne vraća termin koji je već
// zauzet (u ovom slučaju 19:00). Koristi se lažni Doctrine upit kako bi se test mogao
// pokrenuti bez stvarne baze podataka.

namespace App\Tests\Repository;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
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

        $metadata = new \Doctrine\ORM\Mapping\ClassMetadata(Reservation::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(Reservation::class)
            ->willReturn($metadata);
        $entityManager->expects($this->once())
            ->method('createQuery')
            ->willReturn($query);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Reservation::class)
            ->willReturn($entityManager);

        $repository = new ReservationRepository($registry);

        $available = $repository->getAvailableTimeSlots($date, $partySize, false);

        $this->assertIsArray($available);
        $this->assertNotContains('19:00', $available, 'Filled time slot should not be available.');
    }
}
