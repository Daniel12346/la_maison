<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType as TypeTextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class ReservationFormType extends AbstractType
{
    protected $repository;
    public function __construct(ReservationRepository $repository)
    {
        $this->repository = $repository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);
        //polja za partySize, date i isPrivate su uvijek prikazana
        $builder
            ->add('partySize')
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            //opcija za privatnu rezervaciju se prikazuje samo ako je odabrani datum petak ili subota
            ->addDependent('isPrivate', ['date'], function (DependentField $field, ?\DateTime $date) {
                if (!$date) {
                    return;
                }
                $dayOfWeek = (int) $date->format('N'); // 5 = petak, 6 = subota
                $options = [
                    'label' => 'Private',
                    'required' => false,
                    'data' => false,
                    'row_attr' => [],
                ];
                if (!($dayOfWeek === 5 || $dayOfWeek === 6)) {
                    // ako nije petak ili subota, opcija za privatnu rezervaciju se skriva, ali i dalje postoji u formi s vrijednošću false, što je važno jer timeSlotovi i dostupnost ovise o isPrivate
                    $options['row_attr']['style'] = 'display:none;';
                }
                $field->add(CheckboxType::class, $options);
            })
            ->addDependent('timeSlot', ['date', 'partySize', "isPrivate"], function (DependentField $field, ?\DateTime $date, ?int $partySize, ?bool $isPrivate) {
                if (!$date || !$partySize) {
                    //polje za timeSlot se prikazuje samo ako je date i partySize popunjeno
                    //isPrivate ne mora biti popunjen jer ima default vrijednost false
                    return;
                }
                //ponuđene opcije za timeSlot ovise o date, partySize i isPrivate i automatski se mijenjaju kad korisnik promijeni vrijednost nekog od tih polja
                $timeSlots = $this->repository->getAvailableTimeSlots($date, $partySize, $isPrivate ?? false);
                $choices = [];
                foreach ($timeSlots as $timeSlot) {
                    $choices[$timeSlot] = new \DateTime($timeSlot);
                }

                $field->add(ChoiceType::class, [
                    'label' => 'Time',
                    'choices' => $choices
                ]);
            })
            //ostala polja se prikazuju ako postoji timeSlot 
            //nema potrebe da korisnik upisuje email, broj mobitela, ime i posebne zahtjeve dok ne otkrije postoji li slobodan timeSlot
            ->addDependent('email', ['timeSlot'],  function (DependentField $field, ?\DateTime $timeSlot) {
                if (!$timeSlot) {
                    return;
                }

                $field->add(EmailType::class, [
                    'label' => 'Email',
                ]);
            })

            ->addDependent('phone', 'timeSlot', function (DependentField $field, ?\DateTime $timeSlot) {
                if (!$timeSlot) {
                    return;
                }

                $field->add(TelType::class, [
                    'label' => 'Phone',
                ]);
            })
            ->addDependent('fullName', 'timeSlot', function (DependentField $field, ?\DateTime $timeSlot) {
                if (!$timeSlot) {
                    return;
                }

                $field->add(TypeTextType::class, [
                    'label' => 'Full Name',
                ]);
            })
            ->addDependent('requests', 'timeSlot', function (DependentField $field, ?\DateTime $timeSlot) {
                if (!$timeSlot) {
                    return;
                }

                $field
                    ->add(TextareaType::class, [
                        'label' => 'Requests',
                        'required' => false,
                    ]);
            })
            ->addDependent('submit', 'timeSlot', function (DependentField $field, ?\DateTime $timeSlot) {
                if (!$timeSlot) {
                    return;
                }

                $field->add(SubmitType::class);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        //polja forma odgovaraju poljima entiteta Reservation
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'exclude_fields' => ['referenceCode', 'status', 'id'], //polja koja nisu uključena u formu
        ]);
    }
}
