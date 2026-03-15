<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('referenceCode', 'Reference Code'),
            TextField::new('fullName', 'Guest Name'),
            DateField::new('date', 'Date')->setSortable(true),
            TimeField::new('timeSlot', 'Time'),
            IntegerField::new('partySize', 'Party Size'),
            ChoiceField::new('status', 'Status')
                ->setChoices([
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed',
                    'Cancelled' => 'cancelled',
                    'Completed' => 'completed',
                ]),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('date'))
            ->add(ChoiceFilter::new('status')->setChoices([
                'Pending' => 'pending',
                'Confirmed' => 'confirmed',
                'Cancelled' => 'cancelled',
                'Completed' => 'completed',
            ]));
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['date' => 'ASC'])
            ->overrideTemplate('crud/index', 'admin/reservation/index.html.twig');
    }

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
