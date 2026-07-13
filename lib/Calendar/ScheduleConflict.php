<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;

/** Zweck: Beschreibt einen read-only Planungskonflikt ohne fremde Persistenzdetails. */
final class ScheduleConflict {
    public function __construct(private string $type, private DateTimeImmutable $start, private DateTimeImmutable $end, private string $label = '') {
        if ($start >= $end || !in_array($type, ['shift','appointment'], true)) throw new InvalidArgumentException('Ungueltiger Planungskonflikt.');
    }
    public function toArray(): array { return ['type'=>$this->type,'start'=>$this->start->format(DATE_ATOM),'end'=>$this->end->format(DATE_ATOM),'label'=>$this->label]; }
}
