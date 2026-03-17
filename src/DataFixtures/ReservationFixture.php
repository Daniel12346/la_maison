<?php

namespace App\DataFixtures;

use App\Entity\Reservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ReservationFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $statuses = ['Pending', 'Confirmed', 'Completed'];
        $requestExamples = [
            'Vegetarian options needed',
            'Allergy: Peanuts',
            'Please arrange window seating',
            'High chair needed',
            'Celebration - champagne toast requested',
            'Business meeting setup preferred',
            'Music playlist available',
        ];

        $now = new \DateTime();

        // Kreiraj 20 rezervacija s različitim karakteristikama
        // Osiguraj da barem dva vremenska termina imaju pun kapacitet (20 gostiju) za obične rezervacije.
        // (To pomaže testirati logiku punih termina u aplikaciji.)
        for ($i = 1; $i <= 20; $i++) {
            $reservation = new Reservation();

            // Izmjenjuj između obične i privatne, otprilike 30% privatne
            // (No, za naše "puni termin" scenarije prisiljavamo na obične rezervacije.)
            $isPrivate = $i % 3 === 0;

            // Definiramo dva "puna" termina sa po 20 gostiju:
            // - dan +1, 18:00 (4 rezervacije x 5 osoba)
            // - dan +2, 19:00 (5 rezervacija x 4 osobe)
            $forceFullSlot1 = $i >= 1 && $i <= 4;
            $forceFullSlot2 = $i >= 5 && $i <= 9;

            if ($forceFullSlot1 || $forceFullSlot2) {
                $isPrivate = false;
            }

            $reservation->setIsPrivate($isPrivate);

            // Varira datume: neke buduće datume
            if ($forceFullSlot1) {
                $dateOffset = 1;
            } elseif ($forceFullSlot2) {
                $dateOffset = 2;
            } else {
                $dateOffset = ($i % 15) + 1;
            }

            $date = clone $now;
            $date->modify("+{$dateOffset} days");
            $reservation->setDate($date);

            // Varira vremenske slotove
            if ($isPrivate) {
                // Privatna: 18:00 ili 21:00
                $time = ($i % 2 === 0) ? '18:00:00' : '21:00:00';
                $reservation->setTimeSlot(\DateTime::createFromFormat('H:i:s', $time));
            } elseif ($forceFullSlot1) {
                $reservation->setTimeSlot(\DateTime::createFromFormat('H:i:s', '18:00:00'));
            } elseif ($forceFullSlot2) {
                $reservation->setTimeSlot(\DateTime::createFromFormat('H:i:s', '19:00:00'));
            } else {
                // Obična: od podne do 21:30 u intervalima od 30 minuta
                $hour = 12 + (($i * 3) % 10);
                $minute = (($i * 17) % 2) === 0 ? '00' : '30';
                $reservation->setTimeSlot(\DateTime::createFromFormat('H:i:s', "{$hour}:{$minute}:00"));
            }

            // Veličina grupe
            if ($isPrivate) {
                $reservation->setPartySize(6 + ($i % 7)); // 6-12 za privatne
            } elseif ($forceFullSlot1) {
                $reservation->setPartySize(5); // 4 rezervacije x 5 = 20 (puni termin)
            } elseif ($forceFullSlot2) {
                $reservation->setPartySize(4); // 5 rezervacija x 4 = 20 (puni termin)
            } else {
                $reservation->setPartySize(1 + ($i % 10)); // 1-10 za obične
            }

            // Potpuno ime
            $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'Robert', 'Lisa', 'James', 'Maria'];
            $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[($i + 3) % count($lastNames)];
            $reservation->setFullName($firstName . ' ' . $lastName);

            // E-pošta
            $reservation->setEmail(strtolower($firstName . '.' . $lastName . '@example.com'));

            // Telefon
            $areaCode = 200 + ($i % 800);
            $exchange = 100 + ($i % 900);
            $number = 1000 + ($i % 9000);
            $reservation->setPhone("+1{$areaCode}{$exchange}{$number}");

            // Status
            $reservation->setStatus($statuses[$i % count($statuses)]);

            // Nekoliko rezervacija s dodatnim zahtjevima (otprilike svaka treća)
            if ($i % 3 === 0) {
                $reservation->setRequests($requestExamples[($i / 3) % count($requestExamples)]);
            }

            $manager->persist($reservation);
        }

        $manager->flush();
    }
}
