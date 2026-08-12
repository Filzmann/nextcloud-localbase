<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use OCP\EventDispatcher\IEventDispatcher;
use Throwable;

final class PersonalDataAggregator {
    public function __construct(private IEventDispatcher $events) {}

    public function collect(PersonalDataRequest $request): array {
        $registry = new PersonalDataProviderRegistryEvent();
        $this->events->dispatchTyped($registry);
        $providers = [];
        $complete = true;

        foreach ($registry->providers() as $appId => $provider) {
            if (!in_array($request->subject()->type(), $provider->supportedSubjectTypes(), true)) {
                $providers[] = ['appId' => $appId, 'status' => 'not_applicable', 'items' => [], 'limitations' => ['subject_type_not_supported']];
                continue;
            }
            try {
                $report = $provider->collect($request);
                $items = array_map(static fn(PersonalDataItem $item): array => $item->toArray(), $report->items());
                $status = $items === [] ? 'not_applicable' : ($report->isComplete() ? 'complete' : 'partial');
                if ($status === 'partial') $complete = false;
                $providers[] = ['appId' => $appId, 'name' => $report->appName() !== '' ? $report->appName() : $appId, 'status' => $status, 'processing' => $report->processing()->toArray(), 'items' => $items, 'limitations' => $report->limitations()];
            } catch (Throwable) {
                $complete = false;
                $providers[] = ['appId' => $appId, 'status' => 'failed', 'items' => [], 'limitations' => ['provider_failed']];
            }
        }

        usort($providers, static function (array $left, array $right): int {
            $leftPriority = $left['appId'] === 'nextcloud_account' ? 0 : 1;
            $rightPriority = $right['appId'] === 'nextcloud_account' ? 0 : 1;
            return $leftPriority <=> $rightPriority;
        });

        return [
            'subject' => $request->subject()->toArray(),
            'complete' => $complete,
            'coverage' => [
                'scopeNotice' => 'Dieser Download umfasst die derzeit angebundenen Auskunftsprovider, aber noch nicht die gesamte Nextcloud-Instanz.',
                'notImplemented' => [
                    'Dateien, Freigaben, Versionen und Papierkorb',
                    'Aktivitäts-, Anmelde-, Sitzungs-, Sicherheits- und Auditprotokolle',
                    'Talk, Kontakte, Mail, Aufgaben, Deck, Formulare, Notizen und weitere Apps',
                    'persönliche App-Einstellungen und angebundene Dienste',
                    'externe Bewerbungsakten ohne sicher authentifizierte Zuordnung zu einer betroffenen Person',
                ],
            ],
            'article15' => [
                'generatedAt' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
                'rights' => [
                    'Berichtigung unrichtiger personenbezogener Daten',
                    'Löschung oder Einschränkung der Verarbeitung, soweit die gesetzlichen Voraussetzungen vorliegen',
                    'Widerspruch gegen eine Verarbeitung, soweit ein Widerspruchsrecht besteht',
                    'Beschwerde bei einer zuständigen Datenschutzaufsichtsbehörde',
                ],
                'contactNote' => 'Anträge zu Berichtigung, Löschung, Einschränkung oder Widerspruch sind an die in Nextcloud beziehungsweise den Organisationsinformationen benannte verantwortliche Stelle oder Datenschutzstelle zu richten.',
            ],
            'registeredProviders' => array_keys($registry->providers()),
            'providers' => $providers,
        ];
    }
}
