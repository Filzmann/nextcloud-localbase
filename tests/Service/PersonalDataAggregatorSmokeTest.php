<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventDispatcher { public function dispatchTyped(object $event): object; }
}

namespace {
    use OCA\LocalBase\Privacy\PersonalDataAggregator;
    use OCA\LocalBase\Privacy\PersonalDataItem;
    use OCA\LocalBase\Privacy\PersonalDataProvider;
    use OCA\LocalBase\Privacy\PersonalDataProcessingInfo;
    use OCA\LocalBase\Privacy\PersonalDataProviderRegistryEvent;
    use OCA\LocalBase\Privacy\PersonalDataReport;
    use OCA\LocalBase\Privacy\PersonalDataRequest;
    use OCA\LocalBase\Privacy\PersonalDataSubject;
    use OCP\EventDispatcher\IEventDispatcher;

    $subject = new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, 'user-17');
    $request = new PersonalDataRequest($subject, 'de', PersonalDataRequest::PURPOSE_SELF_SERVICE, 50);
    if ((new PersonalDataRequest($subject, 'de', PersonalDataRequest::PURPOSE_SELF_SERVICE))->limit() !== 500) throw new RuntimeException('Self-Service fragt nicht bis zur vertraglichen Obergrenze an.');

    $complete = new class implements PersonalDataProvider {
        public function appId(): string { return 'adroom'; }
        public function supportedSubjectTypes(): array { return [PersonalDataSubject::NEXTCLOUD_USER]; }
        public function collect(PersonalDataRequest $request): PersonalDataReport {
            return new PersonalDataReport([
                new PersonalDataItem(
                    'booking',
                    'Raumbuchung am 12. August 2026',
                    'booking:17',
                    ['Raum' => 'Besprechung 1', 'Titel' => 'Team'],
                    'Organisation der Raumnutzung',
                    'Bis 11. September 2026, danach administrative Prüfung',
                    'Folgende Raumbuchungen sind mit deinen Daten gespeichert:',
                    dataType: 'Raumbuchung',
                ),
            ], new PersonalDataProcessingInfo(
                purposes: ['Raumplanung'],
                categories: ['Buchungsdaten'],
                recipients: ['Angemeldete Nutzer*innen'],
                source: 'Eingaben der buchenden Person',
                retentionCriteria: 'Bis zur administrativen Prüfung',
                thirdCountryTransfers: 'Keine vorgesehen',
                automatedDecisionMaking: 'Keine Entscheidungen mit rechtlicher Wirkung',
            ));
        }
    };
    $notApplicable = new class implements PersonalDataProvider {
        public function appId(): string { return 'empty'; }
        public function supportedSubjectTypes(): array { return [PersonalDataSubject::NEXTCLOUD_USER]; }
        public function collect(PersonalDataRequest $request): PersonalDataReport {
            return new PersonalDataReport([], new PersonalDataProcessingInfo(['Leere Prüfung'], ['Keine'], ['Keine'], 'Keine', 'Keine Speicherung', 'Keine', 'Keine'));
        }
    };
    $failed = new class implements PersonalDataProvider {
        public function appId(): string { return 'broken'; }
        public function supportedSubjectTypes(): array { return [PersonalDataSubject::NEXTCLOUD_USER]; }
        public function collect(PersonalDataRequest $request): PersonalDataReport { throw new RuntimeException('interner Fehler'); }
    };

    $dispatcher = new class([$complete, $notApplicable, $failed]) implements IEventDispatcher {
        public function __construct(private array $providers) {}
        public function dispatchTyped(object $event): object {
            foreach ($this->providers as $provider) $event->register($provider);
            return $event;
        }
    };

    $result = (new PersonalDataAggregator($dispatcher))->collect($request);
    if ($result['subject'] !== ['type' => 'nextcloud_user', 'id' => 'user-17']) throw new RuntimeException('Subject wurde verändert.');
    if ($result['complete'] !== false || array_column($result['providers'], 'appId') !== ['adroom', 'broken', 'empty']) throw new RuntimeException('Provider-Snapshot oder Vollständigkeit ist falsch.');
    $providers = array_column($result['providers'], null, 'appId');
    $roomItem = $providers['adroom']['items'][0];
    if ($providers['adroom']['status'] !== 'complete' || $roomItem['reference'] !== 'booking:17' || $roomItem['attributes']['Titel'] !== 'Team') throw new RuntimeException('Vollständiger Providerbericht oder stabile Referenz fehlt.');
    foreach (['Dateien, Freigaben, Versionen und Papierkorb', 'Aktivitäts-, Anmelde-, Sitzungs-, Sicherheits- und Auditprotokolle', 'Talk, Kontakte, Mail, Aufgaben, Deck, Formulare, Notizen und weitere Apps', 'persönliche App-Einstellungen und angebundene Dienste', 'externe Bewerbungsakten ohne sicher authentifizierte Zuordnung zu einer betroffenen Person'] as $missingSource) {
        if (!in_array($missingSource, $result['coverage']['notImplemented'], true)) throw new RuntimeException("Hinweis auf noch nicht implementierten Datenabruf fehlt: {$missingSource}");
    }
    if (!str_contains($result['coverage']['scopeNotice'], 'gesamte Nextcloud-Instanz')) throw new RuntimeException('Instanzweite Vollständigkeitsgrenze wird nicht verständlich ausgewiesen.');

    $account = new class implements PersonalDataProvider {
        public function appId(): string { return 'nextcloud_account'; }
        public function supportedSubjectTypes(): array { return [PersonalDataSubject::NEXTCLOUD_USER]; }
        public function collect(PersonalDataRequest $request): PersonalDataReport { return new PersonalDataReport([], processingInfo(), appName: 'Nextcloud'); }
    };
    $orderingEvents = new class([$complete, $account]) implements IEventDispatcher {
        public function __construct(private array $providers) {}
        public function dispatchTyped(object $event): object { foreach ($this->providers as $provider) $event->register($provider); return $event; }
    };
    $ordered = (new PersonalDataAggregator($orderingEvents))->collect($request);
    if (array_column($ordered['providers'], 'appId') !== ['nextcloud_account', 'adroom']) throw new RuntimeException('Nextcloud-Stammdaten stehen nicht unabhängig von der Listener-Reihenfolge am Anfang.');
    foreach (['summary', 'purpose', 'retention', 'sectionTitle', 'dataType'] as $field) if (trim((string)($roomItem[$field] ?? '')) === '') throw new RuntimeException("Menschenlesbare Datensatzangabe fehlt: {$field}");
    if ($providers['adroom']['processing']['purposes'] !== ['Raumplanung'] || $providers['adroom']['processing']['retentionCriteria'] !== 'Bis zur administrativen Prüfung') throw new RuntimeException('Art.-15-Verarbeitungsangaben fehlen.');
    foreach (['generatedAt', 'rights', 'contactNote'] as $field) if (!array_key_exists($field, $result['article15'])) throw new RuntimeException("Zentrale Art.-15-Angabe fehlt: {$field}");
    if ($providers['empty']['status'] !== 'not_applicable' || $providers['broken']['status'] !== 'failed') throw new RuntimeException('Leerer oder fehlgeschlagener Provider wird falsch ausgewiesen.');
    if (isset($providers['broken']['error']) || str_contains(json_encode($result, JSON_THROW_ON_ERROR), 'interner Fehler')) throw new RuntimeException('Interne Providerfehler dürfen nicht ausgegeben werden.');

    $registry = new PersonalDataProviderRegistryEvent();
    $registry->register($complete);
    try {
        $registry->register($complete);
        throw new RuntimeException('Doppelte Provider-App-ID wurde akzeptiert.');
    } catch (InvalidArgumentException) {
    }

    foreach ([
        static fn() => new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, ''),
        static fn() => new PersonalDataItem('booking', 'Raumbuchung', 'booking:17', ['secret' => ['verschachtelt']]),
        static fn() => new PersonalDataItem('booking', 'Raumbuchung', '', []),
        static fn() => new PersonalDataItem('booking', 'Raumbuchung', 'booking:17', [], '', 'Bis zur Löschung', 'Buchungen:', dataType: 'Raumbuchung'),
        static fn() => new PersonalDataProcessingInfo([], ['Daten'], ['Empfänger'], 'Quelle', 'Kriterium', 'Keine', 'Keine'),
    ] as $invalid) {
        try {
            $invalid();
            throw new RuntimeException('Ungültiger Datenschutzvertrag wurde akzeptiert.');
        } catch (InvalidArgumentException) {
        }
    }

    echo "PersonalDataAggregatorSmokeTest: OK\n";
}
