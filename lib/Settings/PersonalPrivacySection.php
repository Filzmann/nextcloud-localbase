<?php

declare(strict_types=1);

namespace OCA\LocalBase\Settings;

use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

final class PersonalPrivacySection implements IIconSection {
    public function __construct(private IURLGenerator $url) {}
    public function getIcon(): string { return $this->url->imagePath('localbase', 'privacy.svg'); }
    public function getID(): string { return 'localbase_privacy'; }
    public function getName(): string { return 'Datenschutz'; }
    public function getPriority(): int { return 10; }
}
