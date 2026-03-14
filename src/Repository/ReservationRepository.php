<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function getAvailableTimeSlots(\DateTime $date, int $partySize, bool $isPrivate): array
    {
        $availableTimeSlots = [];
        //vrijeme zatvaranja se razlikuje za privatne i obične rezervacije
        $openingTime = $isPrivate ? 18 : 12;
        $closingTime = 22;

        //za svaki sat od otvaranja do zatvaranja u dostupne timeSlotove dodajemo dva timeSlota (npr za 18:00 dodajemo 18:00 i 18:30)
        for ($i = $openingTime; $i < $closingTime; $i++) {
            $availableTimeSlots[] = new \DateTime($i . ':00:00')->format('H:i');
            $availableTimeSlots[] = new \DateTime($i . ':30:00')->format('H:i');
        }

        $entityManager = $this->getEntityManager();
        //tražimo timeSlotove koji su popunjeni za odabrani datum
        //promatramo samo rezervacije na taj datum koje nisu cancelled i grupiramo ih prema timeSlotu kako bismo mogli dobiti ukupan zbroj gostiju u tom timeSlotu
        //timeSlot je popunjen ako je zbroj već postojećih gostiju i partySize (broj gostiju za novu korisničku rezervaciju) veći od 20
        //u izračunu se uključuju i potvrđene ("Confirmed") i rezervacije koje su još uvijek u procesu ("Pending")
        $filledTimeSlotsQuery = !$isPrivate ? $entityManager->createQuery(
            "SELECT r.timeSlot FROM App\Entity\Reservation r
            WHERE r.date = :date AND r.isPrivate = FALSE AND r.status != 'Cancelled'
            GROUP BY r.timeSlot
            HAVING SUM(r.partySize)+ :partySize > 20"
        )->setParameter('date', $date)->setParameter('partySize', $partySize)
            //budući da je moguća samo jedna privatna rezervacija u jednom timeSlotu, nije potrebno grupirati rezervacije
            //ako postoji privatna rezervacija u nekom timeSlotu, on je popunjen (za privatne rezervacije)
            : $entityManager->createQuery(
                "SELECT r.timeSlot FROM App\Entity\Reservation r
            WHERE r.date = :date AND r.isPrivate = TRUE AND r.status != 'Cancelled' "
            )->setParameter('date', $date);
        $filledTimeSlots = $filledTimeSlotsQuery->getResult();
        //izbacujemo timeSlotove koji su popunjeni iz dostupnih
        foreach ($filledTimeSlots as $filledTimeSlot) {
            $index = array_search($filledTimeSlot["timeSlot"]->format('H:i'), $availableTimeSlots);
            if ($index !== false) {
                unset($availableTimeSlots[$index]);
            }
        }
        return $availableTimeSlots;
    }

    public function sumPartySizeByDateAndTimeSlot(\DateTime $date, \DateTime $timeSlot, ?int $excludeId = null): int
    {
        $qb = $this->createQueryBuilder('r')
            //COALESCE vraća 0 ako nema rezultata
            ->select('COALESCE(SUM(r.partySize), 0) as total')
            ->andWhere('r.date = :date')
            ->andWhere('r.timeSlot = :timeSlot')
            ->andWhere("r.status != 'Cancelled'")
            ->setParameter('date', $date)
            ->setParameter('timeSlot', $timeSlot);

        if (null !== $excludeId) {
            $qb->andWhere('r.id != :id')
                ->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
