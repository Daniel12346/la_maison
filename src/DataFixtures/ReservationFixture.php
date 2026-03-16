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
        for ($i = 1; $i <= 20; $i++) {
            $reservation = new Reservation();

            // Izmjenjuj između obične i privatne, otprilike 30% privatne
            $isPrivate = $i % 3 === 0;
            $reservation->setIsPrivate($isPrivate);

            // Varira datume: neke buduće datume
            $dateOffset = ($i % 15) + 1;
            $date = clone $now;
            $date->modify("+{$dateOffset} days");
            $reservation->setDate($date);

            // Varira vremenske slotove
            if ($isPrivate) {
                // Privatna: 18:00 ili 21:00
                $time = ($i % 2 === 0) ? '18:00:00' : '21:00:00';
                $reservation->setTimeSlot(\DateTime::createFromFormat('H:i:s', $time));
            } else {
                // Obična: od podne do 21:30 u intervalima od 30 minuta
                $hour = 12 + (($i * 3) % 10);
                $minute = (($i * 17) % 2) === 0 ? '00' : '30';
                $reservation->setTimeSlot(\DateTime::createFromFormat('H:i:s', "{$hour}:{$minute}:00"));
            }

            // Veličina grupe
            if ($isPrivate) {
                $reservation->setPartySize(6 + ($i % 7)); // 6-12 za privatne
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
