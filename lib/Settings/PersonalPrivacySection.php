<?php

declare(strict_types=1);

namespace OCA\LocalBase\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

final class PersonalPrivacySection implements IIconSection {
    public function __construct(private IURLGenerator $url, private ?IL10N $l10n = null) {}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }
    public function getIcon(): string { return $this->url->imagePath('localbase', 'privacy.svg'); }
    public function getID(): string { return 'localbase_privacy'; }
    public function getName(): string { return $this->t('Datenschutz'); }
    public function getPriority(): int { return 10; }
}
