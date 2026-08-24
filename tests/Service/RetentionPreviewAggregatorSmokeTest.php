<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventDispatcher { public function dispatchTyped(object $event): object; }
}

namespace {
    use OCA\LocalBase\Privacy\PersonalDataSubject;
    use OCA\LocalBase\Privacy\RetentionPreviewAggregator;
    use OCA\LocalBase\Privacy\RetentionPreviewCandidate;
    use OCA\LocalBase\Privacy\RetentionPreviewPage;
    use OCA\LocalBase\Privacy\RetentionPreviewRequest;
    use OCA\LocalBase\Privacy\RetentionProvider;
    use OCP\EventDispatcher\IEventDispatcher;

    $provider = new class implements RetentionProvider {
        public function appId(): string { return 'adroom'; }
        public function preview(RetentionPreviewRequest $request): RetentionPreviewPage {
            return new RetentionPreviewPage([
                new RetentionPreviewCandidate('booking:41', 'booking', RetentionPreviewCandidate::REVIEW, 'Kein freigegebener Fristtrigger'),
            ]);
        }
    };
    $dispatcher = new class($provider) implements IEventDispatcher {
        public function __construct(private RetentionProvider $provider) {}
        public function dispatchTyped(object $event): object { $event->register($this->provider); return $event; }
    };

    $request = new RetentionPreviewRequest(new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, 'user-17'), 25);
    $result = (new RetentionPreviewAggregator($dispatcher))->preview($request);
    if ($result['dryRun'] !== true || $result['complete'] !== true || $result['providers'][0]['status'] !== 'complete') throw new RuntimeException('Dry-Run-Status ist falsch.');
    $candidate = $result['providers'][0]['candidates'][0];
    if ($candidate['action'] !== 'REVIEW' || $candidate['reference'] !== 'booking:41') throw new RuntimeException('Review-Kandidat fehlt.');
    if (isset($candidate['execute']) || isset($result['execute'])) throw new RuntimeException('Pilot-Dry-Run bietet eine Ausführung an.');

    foreach ([0, 501] as $limit) {
        try {
            new RetentionPreviewRequest(new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, 'user-17'), $limit);
            throw new RuntimeException('Ungültiges Retention-Limit wurde akzeptiert.');
        } catch (InvalidArgumentException) {
        }
    }

    echo "RetentionPreviewAggregatorSmokeTest: OK\n";
}
