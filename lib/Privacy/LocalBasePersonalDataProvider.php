<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FlzDataProtection\PublicApi\V1\ProviderDescriptor;
use OCA\LocalBase\Service\FlzSuiteAdminLayoutService;
use OCP\IAppConfig;
use OCP\IUserManager;

final class LocalBasePersonalDataProvider implements PersonalDataProvider {
    private const APP_ID = 'localbase';
    private const REGISTRY_KEY = 'demo_account_registry';

    public function __construct(
        private FlzSuiteAdminLayoutService $layouts,
        private IAppConfig $config,
        private IUserManager $users,
    ) {}

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor(self::APP_ID, 'Lokale Nextcloud-Basis', '1.0', ['nextcloud-user'], ['personal-data'], 20);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        if ($request->subject()->subjectType() !== 'nextcloud-user') return new PersonalDataPage('not_applicable');
        if ($request->cursor() !== null) throw new InvalidArgumentException('LocalBase does not support cursor paging.');

        $uid = $request->subject()->subjectId();
        $entries = [];
        $restrictions = [];
        $layout = $this->layouts->personalDataForUid($uid);
        if ($layout !== null) $entries[] = $this->layoutEntry($layout);

        try {
            $registration = $this->registrationForUid($uid);
            if ($registration !== null) $entries[] = $this->demoRegistrationEntry($registration);
        } catch (\Throwable) {
            $restrictions[] = 'Die Demo-Registry ist beschädigt und konnte nicht sicher ausgewertet werden.';
        }

        if ($entries === [] && $restrictions === []) return new PersonalDataPage('not_applicable');
        if (count($entries) > $request->pageLimit()) {
            $entries = array_slice($entries, 0, $request->pageLimit());
            $restrictions[] = 'Ausgabelimit erreicht; weitere LocalBase-Personenwerte können vorhanden sein.';
        }

        return new PersonalDataPage($restrictions === [] ? 'complete' : 'partial', $entries, $restrictions);
    }

    private function layoutEntry(array $layout): PersonalDataEntry {
        return new PersonalDataEntry(
            categoryId: 'admin-layout',
            categoryLabel: 'Persönliches Filzmann-Adminlayout',
            reference: 'admin-layout',
            summary: 'Persönliche Anordnung des gemeinsamen Filzmann-Adminbereichs',
            purpose: 'Geräteübergreifende Wiederherstellung der persönlichen Adminansicht',
            source: 'Eigene Bedienung des gemeinsamen Filzmann-Adminbereichs',
            recipientCategories: ['Betroffene Person'],
            retention: 'Bis zum persönlichen Reset oder zur Löschung des Nextcloud-Kontos.',
            thirdCountryTransfer: 'LocalBase übermittelt das persönliche Layout nicht an Drittländer.',
            automatedDecision: 'Das Layout beeinflusst keine fachliche Entscheidung oder Berechtigung.',
            thirdPartyContentNotice: null,
            attributes: [
                'Version' => (int)($layout['version'] ?? 1),
                'Hauptbereich-Reihenfolge' => $this->encodedList($layout['scopes']['main']['order'] ?? []),
                'Eingeklappte Hauptbereiche' => $this->encodedList($layout['scopes']['main']['collapsed'] ?? []),
                'Organigramm-Zoom' => (int)($layout['organigram']['zoom'] ?? 100),
            ],
        );
    }

    /** @param array{ownerAppId:string,backendClass:string} $registration */
    private function demoRegistrationEntry(array $registration): PersonalDataEntry {
        return new PersonalDataEntry(
            categoryId: 'demo-account',
            categoryLabel: 'Registriertes synthetisches Demokonto',
            reference: 'demo-account-registration',
            summary: 'Zuordnung eines synthetischen Kontos zu seinem Demo-Pack',
            purpose: 'Sichere, idempotente Bereitstellung ausdrücklich registrierter lokaler Demo-Packs',
            source: 'Lokale Demo-Provisionierung über Nextcloud-Benutzerverwaltung',
            recipientCategories: ['Technische Administration des lokalen Demosystems'],
            retention: 'Nur solange das synthetische Nextcloud-Konto besteht.',
            thirdCountryTransfer: 'LocalBase übermittelt die Demo-Registrierung nicht an Drittländer.',
            automatedDecision: 'Die Registrierung autorisiert ausschließlich die erneute Provisionierung desselben Demo-Packs und erteilt keine Fachrechte.',
            thirdPartyContentNotice: null,
            attributes: [
                'Demo-Pack' => $registration['ownerAppId'],
                'Nextcloud-Benutzerbackend' => $registration['backendClass'],
            ],
        );
    }

    /** @return array{ownerAppId:string,backendClass:string}|null */
    private function registrationForUid(string $uid): ?array {
        $raw = $this->config->getValueString(self::APP_ID, self::REGISTRY_KEY, '');
        if ($raw === '') return null;
        $registry = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($registry)) throw new \RuntimeException('Invalid demo registry.');
        $registration = $registry[$uid] ?? null;
        if (!is_array($registration)) return null;
        $ownerAppId = $registration['ownerAppId'] ?? null;
        $backendClass = $registration['backendClass'] ?? null;
        if (!is_string($ownerAppId) || $ownerAppId === '' || !is_string($backendClass) || $backendClass === '') {
            throw new \RuntimeException('Invalid demo registration.');
        }
        $user = $this->users->get($uid);
        if ($user === null || $user->getBackendClassName() !== $backendClass) return null;
        return ['ownerAppId' => $ownerAppId, 'backendClass' => $backendClass];
    }

    private function encodedList(mixed $value): string {
        return json_encode(is_array($value) ? array_values($value) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
