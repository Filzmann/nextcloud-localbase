<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use OCP\Accounts\IAccountManager;
use OCP\IGroupManager;
use OCP\IUserManager;

final class NextcloudAccountPersonalDataProvider implements PersonalDataProvider {
    private const PROFILE_LABELS = [
        'displayname' => 'Anzeigename',
        'email' => 'E-Mail-Adresse',
        'additional_mail' => 'Zusätzliche E-Mail-Adresse',
        'phone' => 'Telefonnummer',
        'address' => 'Adresse',
        'website' => 'Webseite',
        'organisation' => 'Organisation',
        'role' => 'Rolle',
        'headline' => 'Profilüberschrift',
        'biography' => 'Biografie',
        'birthdate' => 'Geburtsdatum',
        'pronouns' => 'Pronomen',
        'fediverse' => 'Fediverse-Adresse',
        'bluesky' => 'Bluesky-Adresse',
        'twitter' => 'Twitter-/X-Adresse',
        'profile_enabled' => 'Öffentliches Profil aktiviert',
        'avatar' => 'Avatar',
    ];

    private const SCOPE_LABELS = [
        'v2-private' => 'Privat',
        'v2-local' => 'Auf dieser Nextcloud-Instanz sichtbar',
        'v2-federated' => 'Auch für verbundene vertrauenswürdige Server sichtbar',
        'v2-published' => 'Öffentlich veröffentlicht',
    ];

    private const VERIFIED_LABELS = [
        '0' => 'Nicht bestätigt',
        '1' => 'Bestätigung läuft',
        '2' => 'Bestätigt',
    ];

    public function __construct(
        private IUserManager $users,
        private IAccountManager $accounts,
        private IGroupManager $groups,
    ) {}

    public function appId(): string { return 'nextcloud_account'; }
    public function supportedSubjectTypes(): array { return [PersonalDataSubject::NEXTCLOUD_USER]; }

    public function collect(PersonalDataRequest $request): PersonalDataReport {
        $user = $this->users->get($request->subject()->id());
        $items = [];
        if ($user !== null) {
            $items[] = new PersonalDataItem(
                'account',
                'Nextcloud-Benutzerkonto „' . $user->getUID() . '“',
                'nextcloud-user:' . $user->getUID(),
                [
                    'Login' => $user->getUID(),
                    'Kontostatus' => $user->isEnabled() ? 'Aktiviert' : 'Deaktiviert',
                    'Anmeldung' => 'Nextcloud verarbeitet ein Anmeldegeheimnis oder nutzt einen angebundenen Identitätsdienst. Passwörter, Prüfsummen und andere geheime Anmeldewerte werden in dieser Auskunft nicht ausgegeben.',
                ],
                'Anmeldung, Kontoverwaltung und Zuordnung deiner Daten in Nextcloud',
                'Bis zur Löschung des Benutzerkontos; gesetzliche oder technisch notwendige Nachhaltefristen können im Einzelfall hinzukommen.',
                'Nextcloud hat für dein Benutzerkonto folgende Angaben gespeichert:',
                dataType: 'Benutzerkonto',
            );

            $account = $this->accounts->getAccount($user);
            $propertyCounts = [];
            foreach ($account->getAllProperties() as $property) {
                $value = trim($property->getValue());
                if ($value === '') continue;
                $name = preg_replace('/#\d+$/', '', $property->getName()) ?: $property->getName();
                $propertyCounts[$name] = ($propertyCounts[$name] ?? 0) + 1;
                $label = self::PROFILE_LABELS[$name] ?? $name;
                if ($propertyCounts[$name] > 1) $label .= ' ' . $propertyCounts[$name];
                $items[] = new PersonalDataItem(
                    'profile',
                    $label,
                    'nextcloud-profile:' . $user->getUID() . ':' . $name . ':' . $propertyCounts[$name],
                    [
                        'Profilfeld' => $label,
                        'Wert' => $value,
                        'Sichtbarkeit' => self::SCOPE_LABELS[$property->getScope()] ?? $property->getScope(),
                        'Bestätigungsstatus' => self::VERIFIED_LABELS[$property->getVerified()] ?? $property->getVerified(),
                    ],
                    'Darstellung deines Profils und Verwendung deiner Kontakt- und Stammdaten in Nextcloud',
                    'Bis du die Profilangabe entfernst oder das Benutzerkonto gelöscht wird; angebundene Benutzerverzeichnisse können eigene Fristen vorgeben.',
                    'Nextcloud hat in deinem Profil folgende Stammdaten gespeichert:',
                    dataType: 'Benutzerprofil',
                );
            }

            $groupIds = $this->groups->getUserGroupIds($user);
            sort($groupIds, SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($groupIds as $groupId) {
                $displayName = trim((string)$this->groups->getDisplayName($groupId));
                $items[] = new PersonalDataItem(
                    'group_membership',
                    'Mitgliedschaft in „' . ($displayName !== '' ? $displayName : $groupId) . '“',
                    'nextcloud-group:' . $user->getUID() . ':' . $groupId,
                    [
                        'Gruppen-ID' => $groupId,
                        'Anzeigename der Gruppe' => $displayName !== '' ? $displayName : 'Kein abweichender Anzeigename',
                    ],
                    'Berechtigungssteuerung, Freigaben und organisatorische Zuordnung innerhalb von Nextcloud',
                    'Bis die Gruppenzuordnung aufgehoben oder das Benutzerkonto gelöscht wird; Nachweise können nach Maßgabe notwendiger Fristen länger bestehen.',
                    'Nextcloud hat für dein Benutzerkonto folgende Gruppenzuordnungen gespeichert:',
                    dataType: 'Gruppenzuordnung',
                );
            }
        }

        return new PersonalDataReport(
            $items,
            new PersonalDataProcessingInfo(
                purposes: ['Bereitstellung und Absicherung des Nextcloud-Benutzerkontos'],
                categories: ['Kontokennung und Kontostatus', 'Nextcloud-Profil- und Kontaktangaben einschließlich Sichtbarkeit und Bestätigungsstatus', 'Nextcloud-Gruppenzuordnungen', 'Nicht auslesbare Anmeldeinformationen oder Verweis auf einen angebundenen Identitätsdienst'],
                recipients: ['Berechtigte Nextcloud-Administrator*innen und die von dir verwendeten Nextcloud-Apps'],
                source: 'Kontoeinrichtung, eigene Profilangaben, Nextcloud-Gruppenverwaltung oder angebundener Identitätsdienst beziehungsweise Benutzerverzeichnis',
                retentionCriteria: 'Bis zur Löschung des Kontos und nach Maßgabe notwendiger gesetzlicher oder technischer Nachhaltefristen.',
                thirdCountryTransfers: 'Die konkrete Infrastruktur- und Hostingkonfiguration ist durch die verantwortliche Stelle zu bewerten.',
                automatedDecisionMaking: 'Es findet durch diesen Auskunftsprovider keine automatisierte Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung statt.',
            ),
            appName: 'Nextcloud',
        );
    }
}
