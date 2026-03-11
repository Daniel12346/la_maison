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

    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
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
        $filledTimeSlotsQuery = !$isPrivate ? $entityManager->createQuery(
            "SELECT r.timeSlot FROM App\Entity\Reservation r
            WHERE r.date = :date AND r.isPrivate = FALSE AND r.status != 'cancelled'
            GROUP BY r.timeSlot
            HAVING SUM(r.partySize)+ :partySize > 20"
        )->setParameter('date', $date)->setParameter('partySize', $partySize)
            //budući da je moguća samo jedna privatna rezervacija u jednom timeSlotu, nije potrebno grupirati rezervacija
            //ako već postoji rezervacija u nekom timeSlotu, on je timeSlot popunjen
            : $entityManager->createQuery(
                "SELECT r.timeSlot FROM App\Entity\Reservation r
            WHERE r.date = :date AND r.isPrivate = TRUE AND r.status != 'cancelled' "
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
}
