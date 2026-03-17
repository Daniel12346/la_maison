<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class ReservationFormLiveComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testTimeSlotAppearsAfterRequiredFieldsAreFilled(): void
    {
        $component = $this->createLiveComponent('ReservationForm');

        $component->submitForm([
            'reservation_form' => [
                'partySize' => 2,
                'date' => '2026-03-21',
                'isPrivate' => 0,
            ],
        ]);

        $html = (string) $component->render();

        self::assertStringContainsString('reservation_form_timeSlot', $html);
        self::assertStringContainsString('Time', $html);
    }
}
