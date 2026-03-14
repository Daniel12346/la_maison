<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use App\Validator\MaxCapacity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[MaxCapacity]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $partySize = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\Range(
        min: 'today',
        max: '+30 days',
        notInRangeMessage: 'Please select a date between today and 30 days from now.'
    )]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $fullName = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[Assert\Callback]
    public function validateTimeSlot(ExecutionContextInterface $context): void
    {
        if (!$this->timeSlot) {
            return;
        }
        $hour = (int)$this->timeSlot->format('H');
        if ($this->isPrivate) {
            if ($hour < 18 || $hour >= 22) {
                $context->buildViolation('For private reservations, time must be between 18:00 and 22:00.')
                    ->atPath('timeSlot')
                    ->addViolation();
            }
        } else {
            if ($hour < 12 || $hour >= 22) {
                $context->buildViolation('Time must be between 12:00 and 22:00.')
                    ->atPath('timeSlot')
                    ->addViolation();
            }
        }
    }

    #[Assert\Callback]
    public function validatePartySize(ExecutionContextInterface $context): void
    {
        if (null === $this->partySize) {
            return;
        }

        $isPrivate = (bool) $this->isPrivate;

        $min = $isPrivate ? 6 : 1;
        $max = $isPrivate ? 12 : 10;

        if ($this->partySize < $min || $this->partySize > $max) {
            $message = $isPrivate
                ? 'For private reservations, party size must be between {{ min }} and {{ max }}.'
                : 'Party size must be between {{ min }} and {{ max }}.';

            $context->buildViolation($message)
                ->setParameter('{{ min }}', (string) $min)
                ->setParameter('{{ max }}', (string) $max)
                ->atPath('partySize')
                ->addViolation();
        }
    }
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $timeSlot = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $requests = null;

    #[ORM\Column(length: 30)]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s\-]+$/',
        message: 'Please enter a valid phone number.'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ['Pending', 'Completed', 'Confirmed', 'Cancelled'])]
    private ?string $status = 'Pending';

    #[ORM\Column]
    private ?bool $isPrivate = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartySize(): ?int
    {
        return $this->partySize;
    }

    public function setPartySize(int $partySize): static
    {
        $this->partySize = $partySize;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTimeSlot(): ?\DateTime
    {
        return $this->timeSlot;
    }

    public function setTimeSlot(\DateTime $timeSlot): static
    {
        $this->timeSlot = $timeSlot;

        return $this;
    }

    public function getRequests(): ?string
    {
        return $this->requests;
    }

    public function setRequests(?string $requests): static
    {
        $this->requests = $requests;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }
}
