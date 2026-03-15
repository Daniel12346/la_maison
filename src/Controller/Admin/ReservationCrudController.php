<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\RequestStack;

class ReservationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly RequestStack $requestStack,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fullyBookedSlotKeys = $this->reservationRepository->getFullyBookedSlotKeys($this->extractFilteredDate());

        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('referenceCode', 'Reference Code'),
            TextField::new('fullName', 'Guest Name'),
            DateField::new('date', 'Date')->setSortable(true),

            TimeField::new('timeSlot', 'Time')
                //dodaje oznaku [FULLY BOOKED] pored vremena ako je taj time slot popunjen za odabrani datum
                //"use" služi za pristup $fullyBookedSlotKeys unutar funkcije formatValue jer je ona closure funkcija i nema pristup vanjskim varijablama osim bez "use"
                ->formatValue(static function ($value, ?Reservation $reservation) use ($fullyBookedSlotKeys): string {
                    $formattedTime = $value instanceof \DateTimeInterface
                        ? $value->format('H:i')
                        : (string) $value;

                    if (!$reservation instanceof Reservation || !$reservation->getDate() || !$reservation->getTimeSlot()) {
                        return $formattedTime;
                    }

                    $slotKey = $reservation->getDate()->format('Y-m-d') . ' ' . $reservation->getTimeSlot()->format('H:i');

                    if (isset($fullyBookedSlotKeys[$slotKey])) {
                        return $formattedTime . ' [FULLY BOOKED]';
                    }

                    return $formattedTime;
                }),
            IntegerField::new('partySize', 'Party Size'),
            BooleanField::new('isPrivate', 'Private'),
            ChoiceField::new('status', 'Status')
                ->setChoices([
                    'Pending' => 'Pending',
                    'Confirmed' => 'Confirmed',
                    'Cancelled' => 'Cancelled',
                    'Completed' => 'Completed',
                ]),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('date'))
            ->add(ChoiceFilter::new('status')->setChoices([
                'Pending' => 'Pending',
                'Confirmed' => 'Confirmed',
                'Cancelled' => 'Cancelled',
                'Completed' => 'Completed',
            ]));
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['date' => 'ASC'])
            ->overrideTemplate('crud/index', 'admin/reservation/index.html.twig');
    }

    //dodaje dodatne parametre koji se mogu koristiti u prilagođenom index templateu
    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $date = $this->extractFilteredDate();
        $totalExpectedGuests = $date
            ? $this->reservationRepository->getTotalGuestsForDay($date)
            : null;

        $responseParameters->set('filteredDate', $date);
        $responseParameters->set('totalExpectedGuests', $totalExpectedGuests);

        return $responseParameters;
    }
    //izvlači datum iz query parametara kako bi se mogao koristiti za označavanje popunjenih time slotova i prikaz ukupnog broja gostiju za taj dan
    private function extractFilteredDate(): ?\DateTimeImmutable
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $filters = $request->query->all('filters');
        $dateValue = $filters['date']['value'] ?? null;

        if (is_string($dateValue) && $dateValue !== '') {
            try {
                return new \DateTimeImmutable($dateValue);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
