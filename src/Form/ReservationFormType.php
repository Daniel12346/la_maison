<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType as TypeTextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class ReservationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);
        $builder
            ->add('partySize')
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            ->add('isPrivate', CheckboxType::class, [
                'label' => 'Private',
            ])
            ->addDependent('timeSlot', ['date', 'partySize', "isPrivate"], function (DependentField $field, ?\DateTime $date, ?int $partySize, ?bool $isPrivate) {
                if (!$date || !$partySize) {
                    return;
                }
                $field->add(ChoiceType::class, [
                    'label' => 'Time'
                ]);
            })

            ->addDependent('email', ['timeSlot'],  function (DependentField $field, ?\DateTime $timeSlot) {
                if (!$timeSlot) {
                    return;
                }

                $field->add(TypeTextType::class, [
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
                    ]);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
