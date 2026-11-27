<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use Throwable;

final class PersonalDataAggregator {
    public function __construct(private IEventDispatcher $events, private ?IL10N $l10n = null) {}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

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
                'scopeNotice' => $this->t('Dieser Download umfasst die derzeit angebundenen Auskunftsprovider, aber noch nicht die gesamte Nextcloud-Instanz.'),
                'notImplemented' => [
                    $this->t('Dateien, Freigaben, Versionen und Papierkorb'),
                    $this->t('Aktivitäts-, Anmelde-, Sitzungs-, Sicherheits- und Auditprotokolle'),
                    $this->t('Talk, Kontakte, Mail, Aufgaben, Deck, Formulare, Notizen und weitere Apps'),
                    $this->t('persönliche App-Einstellungen und angebundene Dienste'),
                    $this->t('externe Bewerbungsakten ohne sicher authentifizierte Zuordnung zu einer betroffenen Person'),
                ],
            ],
            'article15' => [
                'generatedAt' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
                'rights' => [
                    $this->t('Berichtigung unrichtiger personenbezogener Daten'),
                    $this->t('Löschung oder Einschränkung der Verarbeitung, soweit die gesetzlichen Voraussetzungen vorliegen'),
                    $this->t('Widerspruch gegen eine Verarbeitung, soweit ein Widerspruchsrecht besteht'),
                    $this->t('Beschwerde bei einer zuständigen Datenschutzaufsichtsbehörde'),
                ],
                'contactNote' => $this->t('Anträge zu Berichtigung, Löschung, Einschränkung oder Widerspruch sind an die in Nextcloud beziehungsweise den Organisationsinformationen benannte verantwortliche Stelle oder Datenschutzstelle zu richten.'),
            ],
            'registeredProviders' => array_keys($registry->providers()),
            'providers' => $providers,
        ];
    }
}
