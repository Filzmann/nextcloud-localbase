<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;

final class PersonalDataReport {
    /** @param list<PersonalDataItem> $items @param list<string> $limitations */
    public function __construct(
        private array $items,
        private PersonalDataProcessingInfo $processing,
        private bool $complete = true,
        private array $limitations = [],
        private string $appName = '',
    ) {
        foreach ($this->items as $item) if (!$item instanceof PersonalDataItem) throw new InvalidArgumentException('Providerbericht enthält ein ungültiges Element.');
        $this->items = array_values($this->items);
        $this->limitations = array_values(array_filter(array_map(static fn(mixed $value): string => trim((string)$value), $this->limitations)));
        $this->appName = trim($this->appName);
        if (strlen($this->appName) > 255) throw new InvalidArgumentException('App-Anzeigename ist ungültig.');
    }

    /** @return list<PersonalDataItem> */
    public function items(): array { return $this->items; }
    public function isComplete(): bool { return $this->complete; }
    public function processing(): PersonalDataProcessingInfo { return $this->processing; }
    public function appName(): string { return $this->appName; }
    /** @return list<string> */
    public function limitations(): array { return $this->limitations; }
}
