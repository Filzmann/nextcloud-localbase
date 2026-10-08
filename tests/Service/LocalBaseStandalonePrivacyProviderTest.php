<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event {}
    interface IEventListener { public function handle(Event $event): void; }
}
namespace OCP {
    interface IAppConfig { public function getValueString(string $appId, string $key, string $default = ''): string; }
    interface IUser { public function getUID(): string; public function getBackendClassName(): string; }
    interface IUserManager { public function get(string $uid): ?IUser; }
}
namespace OCA\FlzDataProtection\PublicApi\V1 {
    final class DataSubjectRef { public function __construct(private string $type, private string $id) {} public function subjectType(): string { return $this->type; } public function subjectId(): string { return $this->id; } }
    final class ProviderDescriptor { public function __construct(private string $appId, private string $name, private string $version, private array $types, private array $capabilities, private int $limit) {} public function appId(): string { return $this->appId; } public function contractVersion(): string { return $this->version; } }
    final class PersonalDataRequest { public function __construct(private DataSubjectRef $subject, private int $limit = 20, private ?string $cursor = null) {} public function subject(): DataSubjectRef { return $this->subject; } public function pageLimit(): int { return $this->limit; } public function cursor(): ?string { return $this->cursor; } }
    final class PersonalDataEntry { public function __construct(private string $categoryId, private string $categoryLabel, private string $reference, private string $summary, private string $purpose, private string $source, private array $recipientCategories, private string $retention, private string $thirdCountryTransfer, private string $automatedDecision, private ?string $thirdPartyContentNotice, private array $attributes) {} public function reference(): string { return $this->reference; } public function attributes(): array { return $this->attributes; } }
    final class PersonalDataPage { public function __construct(private string $status, private array $entries = [], private array $restrictions = [], private ?string $nextCursor = null) {} public function status(): string { return $this->status; } public function entries(): array { return $this->entries; } public function restrictions(): array { return $this->restrictions; } }
    interface PersonalDataProvider { public function descriptor(): ProviderDescriptor; public function collect(PersonalDataRequest $request): PersonalDataPage; }
    final class RegisterPersonalDataProvidersEvent extends \OCP\EventDispatcher\Event { public array $providers = []; public function register(PersonalDataProvider $provider): void { $this->providers[$provider->descriptor()->appId()] = $provider; } }
    final class ProcessingMetadataProviderDescriptor { public function __construct(private string $appId, private string $name, private string $version) {} public function appId(): string { return $this->appId; } }
    final class ProcessingMetadataCatalog { private function __construct(private array $payload) {} public static function fromArray(array $payload): self { return new self($payload); } public function appId(): string { return $this->payload['app_id']; } public function processingIds(): array { return array_column($this->payload['processings'], 'processing_id'); } }
    interface ProcessingMetadataProvider { public function descriptor(): ProcessingMetadataProviderDescriptor; public function catalog(): ProcessingMetadataCatalog; }
    final class RegisterProcessingMetadataProvidersEvent extends \OCP\EventDispatcher\Event { public array $providers = []; public function register(ProcessingMetadataProvider $provider): void { $this->providers[$provider->descriptor()->appId()] = $provider; } }
}
namespace OCA\LocalBase\Service {
    class FlzSuiteAdminLayoutService {
        public function personalDataForUid(string $uid): ?array {
            return $uid === 'subject' ? ['version' => 1, 'scopes' => ['main' => ['order' => ['directory'], 'collapsed' => []]], 'organigram' => ['zoom' => 120]] : null;
        }
    }
}

namespace {
    use OCA\FlzDataProtection\PublicApi\V1\DataSubjectRef;
    use OCA\FlzDataProtection\PublicApi\V1\PersonalDataRequest;
    use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
    use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
    use OCA\LocalBase\Privacy\LocalBasePersonalDataProvider;
    use OCA\LocalBase\Privacy\LocalBasePersonalDataProviderListener;
    use OCA\LocalBase\Privacy\LocalBaseProcessingMetadataProvider;
    use OCA\LocalBase\Privacy\LocalBaseProcessingMetadataProviderListener;
    use OCA\LocalBase\Service\FlzSuiteAdminLayoutService;
    use OCP\IAppConfig;
    use OCP\IUser;
    use OCP\IUserManager;

    $config = new class implements IAppConfig {
        public string $registry;
        public function __construct() { $this->registry = json_encode([
            'subject' => ['ownerAppId' => 'flz-full-suite-demo', 'backendClass' => 'OC\\User\\Database'],
            'foreign' => ['ownerAppId' => 'flz-full-suite-demo', 'backendClass' => 'OC\\User\\Database'],
        ], JSON_THROW_ON_ERROR); }
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->registry; }
    };
    $users = new class implements IUserManager {
        public function get(string $uid): ?IUser {
            if (!in_array($uid, ['subject', 'foreign'], true)) return null;
            return new class($uid) implements IUser {
                public function __construct(private string $uid) {}
                public function getUID(): string { return $this->uid; }
                public function getBackendClassName(): string { return 'OC\\User\\Database'; }
            };
        }
    };
    $provider = new LocalBasePersonalDataProvider(new FlzSuiteAdminLayoutService(), $config, $users);
    $page = $provider->collect(new PersonalDataRequest(new DataSubjectRef('nextcloud-user', 'subject')));
    if ($page->status() !== 'complete' || array_map(static fn($entry): string => $entry->reference(), $page->entries()) !== ['admin-layout', 'demo-account-registration']) throw new RuntimeException('LocalBase-eigene Personenwerte werden nicht vollständig projiziert.');
    $encoded = json_encode(array_map(static fn($entry): array => $entry->attributes(), $page->entries()), JSON_THROW_ON_ERROR);
    if (!str_contains($encoded, 'flz-full-suite-demo') || str_contains($encoded, 'foreign')) throw new RuntimeException('Provider ist nicht strikt an die angefragte UID gebunden.');
    $unsupported = $provider->collect(new PersonalDataRequest(new DataSubjectRef('external-applicant', 'subject')));
    if ($unsupported->status() !== 'not_applicable' || $unsupported->entries() !== []) throw new RuntimeException('Nicht unterstützter Subject-Typ erhält LocalBase-Daten.');
    $limited = $provider->collect(new PersonalDataRequest(new DataSubjectRef('nextcloud-user', 'subject'), 1));
    if ($limited->status() !== 'partial' || count($limited->entries()) !== 1 || $limited->restrictions() === []) throw new RuntimeException('Ausgabelimit wird als vollständige Auskunft behauptet.');

    $personalRegistration = new RegisterPersonalDataProvidersEvent();
    (new LocalBasePersonalDataProviderListener($provider))->handle($personalRegistration);
    if (($personalRegistration->providers['localbase'] ?? null) !== $provider) throw new RuntimeException('PersonalDataProvider wird nicht lazy registriert.');

    $metadata = new LocalBaseProcessingMetadataProvider();
    if ($metadata->catalog()->processingIds() !== ['personal_admin_layout', 'demo_account_registry']) throw new RuntimeException('Processing-Katalog ist unvollständig.');
    $metadataRegistration = new RegisterProcessingMetadataProvidersEvent();
    (new LocalBaseProcessingMetadataProviderListener($metadata))->handle($metadataRegistration);
    if (($metadataRegistration->providers['localbase'] ?? null) !== $metadata) throw new RuntimeException('ProcessingMetadataProvider wird nicht lazy registriert.');

    echo "LocalBase standalone privacy provider test passed\n";
}
