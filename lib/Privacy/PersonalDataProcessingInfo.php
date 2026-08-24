<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;

final class PersonalDataProcessingInfo {
    /**
     * @param list<string> $purposes
     * @param list<string> $categories
     * @param list<string> $recipients
     */
    public function __construct(
        private array $purposes,
        private array $categories,
        private array $recipients,
        private string $source,
        private string $retentionCriteria,
        private string $thirdCountryTransfers,
        private string $automatedDecisionMaking,
    ) {
        $this->purposes = $this->normalizeList($purposes);
        $this->categories = $this->normalizeList($categories);
        $this->recipients = $this->normalizeList($recipients);
        $this->source = $this->required($source);
        $this->retentionCriteria = $this->required($retentionCriteria);
        $this->thirdCountryTransfers = $this->required($thirdCountryTransfers);
        $this->automatedDecisionMaking = $this->required($automatedDecisionMaking);
    }

    public function toArray(): array {
        return [
            'purposes' => $this->purposes,
            'categories' => $this->categories,
            'recipients' => $this->recipients,
            'source' => $this->source,
            'retentionCriteria' => $this->retentionCriteria,
            'thirdCountryTransfers' => $this->thirdCountryTransfers,
            'automatedDecisionMaking' => $this->automatedDecisionMaking,
        ];
    }

    private function normalizeList(array $values): array {
        if (!array_is_list($values)) throw new InvalidArgumentException('Art.-15-Liste ist ungültig.');
        $normalized = array_values(array_unique(array_map(fn(mixed $value): string => $this->required((string)$value), $values)));
        if ($normalized === []) throw new InvalidArgumentException('Art.-15-Liste darf nicht leer sein.');
        return $normalized;
    }

    private function required(string $value): string {
        $value = trim($value);
        if ($value === '' || strlen($value) > 2000) throw new InvalidArgumentException('Art.-15-Angabe ist ungültig.');
        return $value;
    }
}
