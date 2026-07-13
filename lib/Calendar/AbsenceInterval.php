<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Zweck: Transportiert eine app-uebergreifend gelesene Abwesenheit ohne Persistenz- oder Rechtewissen.
 * Vertrag: Zeitraeume sind halboffen [Beginn, Ende); nur planned und approved sind zulaessig.
 */
final class AbsenceInterval {
    public const STATUS_PLANNED = 'planned';
    public const STATUS_APPROVED = 'approved';

    public function __construct(
        private string $employeeUid,
        private DateTimeImmutable $start,
        private DateTimeImmutable $end,
        private string $status,
        private string $label = 'Urlaub',
    ) {
        if ($employeeUid === '' || $start >= $end) throw new InvalidArgumentException('Ungueltiger Abwesenheitszeitraum.');
        if (!in_array($status, [self::STATUS_PLANNED, self::STATUS_APPROVED], true)) throw new InvalidArgumentException('Ungueltiger Abwesenheitsstatus.');
    }

    public function employeeUid(): string { return $this->employeeUid; }
    public function start(): DateTimeImmutable { return $this->start; }
    public function end(): DateTimeImmutable { return $this->end; }
    public function status(): string { return $this->status; }
    public function approved(): bool { return $this->status === self::STATUS_APPROVED; }
    public function overlaps(DateTimeImmutable $start, DateTimeImmutable $end): bool { return $this->start < $end && $this->end > $start; }

    /** @return array{employeeUid:string,start:string,end:string,status:string,label:string,marker:string,blocks:bool} */
    public function toArray(): array {
        return [
            'employeeUid' => $this->employeeUid,
            'start' => $this->start->format(DATE_ATOM),
            'end' => $this->end->format(DATE_ATOM),
            'status' => $this->status,
            'label' => $this->label,
            'marker' => $this->approved() ? 'U' : 'U?',
            'blocks' => $this->approved(),
        ];
    }
}
