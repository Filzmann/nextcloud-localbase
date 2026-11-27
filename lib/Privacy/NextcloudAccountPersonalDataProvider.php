<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use OCP\Accounts\IAccountManager;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;

final class NextcloudAccountPersonalDataProvider implements PersonalDataProvider {
    private function profile_labels(): array {
        return [
        'displayname' => 'Anzeigename',
        'email' => 'E-Mail-Adresse',
        'additional_mail' => $this->t('Zusätzliche E-Mail-Adresse'),
        'phone' => 'Telefonnummer',
        'address' => $this->t('Adresse'),
        'website' => 'Webseite',
        'organisation' => 'Organisation',
        'role' => $this->t('Rolle'),
        'headline' => $this->t('Profilüberschrift'),
        'biography' => 'Biografie',
        'birthdate' => 'Geburtsdatum',
        'pronouns' => 'Pronomen',
        'fediverse' => 'Fediverse-Adresse',
        'bluesky' => 'Bluesky-Adresse',
        'twitter' => 'Twitter-/X-Adresse',
        'profile_enabled' => $this->t('Öffentliches Profil aktiviert'),
        'avatar' => 'Avatar',
        ];
    }

    private function scope_labels(): array {
        return [
        'v2-private' => 'Privat',
        'v2-local' => $this->t('Auf dieser Nextcloud-Instanz sichtbar'),
        'v2-federated' => $this->t('Auch für verbundene vertrauenswürdige Server sichtbar'),
        'v2-published' => $this->t('Öffentlich veröffentlicht'),
        ];
    }

    private function verified_labels(): array {
        return [
        '0' => $this->t('Nicht bestätigt'),
        '1' => $this->t('Bestätigung läuft'),
        '2' => $this->t('Bestätigt'),
        ];
    }

    public function __construct(private IUserManager $users,
        private IAccountManager $accounts,
        private IGroupManager $groups,
        private ?IL10N $l10n = null) {}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    public function appId(): string { return 'nextcloud_account'; }
    public function supportedSubjectTypes(): array { return [PersonalDataSubject::NEXTCLOUD_USER]; }

    public function collect(PersonalDataRequest $request): PersonalDataReport {
        $user = $this->users->get($request->subject()->id());
        $items = [];
        if ($user !== null) {
            $items[] = new PersonalDataItem(
                'account',
                $this->t('Nextcloud-Benutzerkonto „') . $user->getUID() . '“',
                'nextcloud-user:' . $user->getUID(),
                [
                    'Login' => $user->getUID(),
                    'Kontostatus' => $user->isEnabled() ? 'Aktiviert' : 'Deaktiviert',
                    'Anmeldung' => $this->t('Nextcloud verarbeitet ein Anmeldegeheimnis oder nutzt einen angebundenen Identitätsdienst. Passwörter, Prüfsummen und andere geheime Anmeldewerte werden in dieser Auskunft nicht ausgegeben.'),
                ],
                $this->t('Anmeldung, Kontoverwaltung und Zuordnung deiner Daten in Nextcloud'),
                $this->t('Bis zur Löschung des Benutzerkontos; gesetzliche oder technisch notwendige Nachhaltefristen können im Einzelfall hinzukommen.'),
                $this->t('Nextcloud hat für dein Benutzerkonto folgende Angaben gespeichert:'),
                dataType: 'Benutzerkonto',
            );

            $account = $this->accounts->getAccount($user);
            $propertyCounts = [];
            foreach ($account->getAllProperties() as $property) {
                $value = trim($property->getValue());
                if ($value === '') continue;
                $name = preg_replace('/#\d+$/', '', $property->getName()) ?: $property->getName();
                $propertyCounts[$name] = ($propertyCounts[$name] ?? 0) + 1;
                $label = $this->profile_labels()[$name] ?? $name;
                if ($propertyCounts[$name] > 1) $label .= ' ' . $propertyCounts[$name];
                $items[] = new PersonalDataItem(
                    'profile',
                    $label,
                    'nextcloud-profile:' . $user->getUID() . ':' . $name . ':' . $propertyCounts[$name],
                    [
                        'Profilfeld' => $label,
                        $this->t('Wert') => $value,
                        'Sichtbarkeit' => $this->scope_labels()[$property->getScope()] ?? $property->getScope(),
                        $this->t('Bestätigungsstatus') => $this->verified_labels()[$property->getVerified()] ?? $property->getVerified(),
                    ],
                    $this->t('Darstellung deines Profils und Verwendung deiner Kontakt- und Stammdaten in Nextcloud'),
                    $this->t('Bis du die Profilangabe entfernst oder das Benutzerkonto gelöscht wird; angebundene Benutzerverzeichnisse können eigene Fristen vorgeben.'),
                    $this->t('Nextcloud hat in deinem Profil folgende Stammdaten gespeichert:'),
                    dataType: 'Benutzerprofil',
                );
            }

            $groupIds = $this->groups->getUserGroupIds($user);
            sort($groupIds, SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($groupIds as $groupId) {
                $displayName = trim((string)$this->groups->getDisplayName($groupId));
                $items[] = new PersonalDataItem(
                    'group_membership',
                    $this->t('Mitgliedschaft in „') . ($displayName !== '' ? $displayName : $groupId) . '“',
                    'nextcloud-group:' . $user->getUID() . ':' . $groupId,
                    [
                        'Gruppen-ID' => $groupId,
                        $this->t('Anzeigename der Gruppe') => $displayName !== '' ? $displayName : $this->t('Kein abweichender Anzeigename'),
                    ],
                    $this->t('Berechtigungssteuerung, Freigaben und organisatorische Zuordnung innerhalb von Nextcloud'),
                    $this->t('Bis die Gruppenzuordnung aufgehoben oder das Benutzerkonto gelöscht wird; Nachweise können nach Maßgabe notwendiger Fristen länger bestehen.'),
                    $this->t('Nextcloud hat für dein Benutzerkonto folgende Gruppenzuordnungen gespeichert:'),
                    dataType: 'Gruppenzuordnung',
                );
            }
        }

        return new PersonalDataReport(
            $items,
            new PersonalDataProcessingInfo(
                purposes: [$this->t('Bereitstellung und Absicherung des Nextcloud-Benutzerkontos')],
                categories: [$this->t('Kontokennung und Kontostatus'), $this->t('Nextcloud-Profil- und Kontaktangaben einschließlich Sichtbarkeit und Bestätigungsstatus'), 'Nextcloud-Gruppenzuordnungen', $this->t('Nicht auslesbare Anmeldeinformationen oder Verweis auf einen angebundenen Identitätsdienst')],
                recipients: ['Berechtigte Nextcloud-Administrator*innen und die von dir verwendeten Nextcloud-Apps'],
                source: $this->t('Kontoeinrichtung, eigene Profilangaben, Nextcloud-Gruppenverwaltung oder angebundener Identitätsdienst beziehungsweise Benutzerverzeichnis'),
                retentionCriteria: $this->t('Bis zur Löschung des Kontos und nach Maßgabe notwendiger gesetzlicher oder technischer Nachhaltefristen.'),
                thirdCountryTransfers: $this->t('Die konkrete Infrastruktur- und Hostingkonfiguration ist durch die verantwortliche Stelle zu bewerten.'),
                automatedDecisionMaking: $this->t('Es findet durch diesen Auskunftsprovider keine automatisierte Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung statt.'),
            ),
            appName: 'Nextcloud',
        );
    }
}
