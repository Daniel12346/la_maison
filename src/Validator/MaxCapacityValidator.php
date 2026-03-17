<?php

namespace App\Validator;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

//prije spremanja rezervacije, provjeravamo hoće li odabrani partySize (broj gostiju) biti unutar kapaciteta od 20 gostiju za odabrani datum i timeSlot
class MaxCapacityValidator extends ConstraintValidator
{
    private ReservationRepository $repository;

    public function __construct(ReservationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Reservation $value
     * @param MaxCapacity $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof Reservation) {
            return;
        }
        $partySize = $value->getPartySize();
        $date = $value->getDate();
        $timeSlot = $value->getTimeSlot();
        $isPrivate = $value->isPrivate();
        //dozvoljena je samo jedna privatna rezervacija po timeSlotu, bez obzira na broj gostiju
        if ($isPrivate) {
            $excludeId = $value->getId();
            $otherPrivate = $this->repository->findOneBy([
                'date' => $date,
                'timeSlot' => $timeSlot,
                'isPrivate' => true,
            ], null, null, $excludeId ? ['id' => $excludeId] : null);
            if ($otherPrivate !== null) {
                $this->context->buildViolation('A private reservation already exists for this date and time slot.')
                    ->atPath('isPrivate')
                    ->addViolation();
                return;
            }

            return;
        }


        if (null === $partySize || null === $date || null === $timeSlot) {
            return;
        }
        //$existing je zbroj partySize-a svih rezervacija koje su već na taj datum i timeSlot (osim trenutne rezervacije ako se radi o updateu)
        //pri njegovu izračunu isključujemo trenutnu rezervaciju, što nije potrebno kod nove rezervacije, ali je važno kod updatea gdje trenutna rezervacija već ima partySize u bazi podataka
        $excludeId = $value->getId();
        $existing = $this->repository->sumPartySizeByDateAndTimeSlot($date, $timeSlot, false, $excludeId);
        $total = $existing + $partySize;

        $max = 20;
        if ($total > $max) {
            $available = max(0, $max - $existing);

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ available }}', (string) $available)
                ->atPath('partySize')
                ->addViolation();
        }
    }
}
