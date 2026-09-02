<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;

/** Zweck: Beschreibt einen read-only Planungskonflikt ohne fremde Persistenzdetails. */
final class ScheduleConflict {
    public function __construct(
        private string $type,
        private DateTimeImmutable $start,
        private DateTimeImmutable $end,
        private string $label = '',
        private string $sourceAppId = '',
    ) {
        if ($start >= $end
            || !in_array($type, ['shift', 'appointment'], true)
            || ($sourceAppId !== '' && preg_match('/^[a-z][a-z0-9_]{0,63}$/', $sourceAppId) !== 1)) {
            throw new InvalidArgumentException('Ungültiger Planungskonflikt.');
        }
    }

    public function type(): string { return $this->type; }
    public function start(): DateTimeImmutable { return $this->start; }
    public function end(): DateTimeImmutable { return $this->end; }
    public function label(): string { return $this->label; }
    public function sourceAppId(): string { return $this->sourceAppId; }

    public function toArray(): array {
        return [
            'type' => $this->type,
            'start' => $this->start->format(DATE_ATOM),
            'end' => $this->end->format(DATE_ATOM),
            'label' => $this->label,
            'sourceAppId' => $this->sourceAppId,
        ];
    }
}
