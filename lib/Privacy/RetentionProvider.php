<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

interface RetentionProvider {
    public function appId(): string;
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage;
}
