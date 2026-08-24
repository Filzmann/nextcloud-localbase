<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;

final class PersonalDataItem {
    /** @param array<string,scalar|null> $attributes */
    public function __construct(
        private string $category,
        private string $label,
        private string $reference,
        private array $attributes,
        private ?string $purpose = null,
        private ?string $retention = null,
        private ?string $sectionTitle = null,
        private ?string $thirdPartyNote = null,
        private ?string $dataType = null,
    ) {
        $this->category = trim($category);
        $this->label = trim($label);
        $this->reference = trim($reference);
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $this->category)) throw new InvalidArgumentException('Datenkategorie ist ungültig.');
        if ($this->label === '' || strlen($this->label) > 255) throw new InvalidArgumentException('Datenbezeichnung ist ungültig.');
        if ($this->reference === '' || strlen($this->reference) > 255) throw new InvalidArgumentException('Datenreferenz ist ungültig.');
        foreach ($this->attributes as $key => $value) {
            if (!is_string($key) || trim($key) === '' || strlen($key) > 100 || (!is_scalar($value) && $value !== null)) {
                throw new InvalidArgumentException('Datenattribut ist ungültig.');
            }
        }
        foreach (['purpose', 'retention', 'sectionTitle'] as $field) {
            $this->{$field} = trim((string)$this->{$field});
            if ($this->{$field} === '' || strlen($this->{$field}) > 2000) throw new InvalidArgumentException('Menschenlesbare Datensatzangabe ist ungültig.');
        }
        if ($this->thirdPartyNote !== null) {
            $this->thirdPartyNote = trim($this->thirdPartyNote);
            if ($this->thirdPartyNote === '' || strlen($this->thirdPartyNote) > 2000) throw new InvalidArgumentException('Drittpersonenhinweis ist ungültig.');
        }
        $this->dataType = trim((string)$this->dataType);
        if ($this->dataType === '' || strlen($this->dataType) > 255) throw new InvalidArgumentException('Menschenlesbarer Datentyp ist ungültig.');
    }

    public function toArray(): array {
        return [
            'category' => $this->category,
            'label' => $this->label,
            'summary' => $this->label,
            'reference' => $this->reference,
            'attributes' => $this->attributes,
            'purpose' => $this->purpose,
            'retention' => $this->retention,
            'sectionTitle' => $this->sectionTitle,
            'thirdPartyNote' => $this->thirdPartyNote,
            'dataType' => $this->dataType,
        ];
    }
}
