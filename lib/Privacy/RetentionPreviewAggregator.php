<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use OCP\EventDispatcher\IEventDispatcher;
use Throwable;

final class RetentionPreviewAggregator {
    public function __construct(private IEventDispatcher $events) {}

    public function preview(RetentionPreviewRequest $request): array {
        $registry = new RetentionProviderRegistryEvent();
        $this->events->dispatchTyped($registry);
        $providers = [];
        $complete = true;
        foreach ($registry->providers() as $appId => $provider) {
            try {
                $page = $provider->preview($request);
                $candidates = array_map(static fn(RetentionPreviewCandidate $candidate): array => $candidate->toArray(), $page->candidates());
                if (!$page->isComplete()) $complete = false;
                $providers[] = ['appId' => $appId, 'status' => $page->isComplete() ? 'complete' : 'partial', 'candidates' => $candidates];
            } catch (Throwable) {
                $complete = false;
                $providers[] = ['appId' => $appId, 'status' => 'failed', 'candidates' => []];
            }
        }
        return ['subject' => $request->subject()->toArray(), 'dryRun' => true, 'complete' => $complete, 'providers' => $providers];
    }
}
