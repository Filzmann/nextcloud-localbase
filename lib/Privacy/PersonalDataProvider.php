<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

interface PersonalDataProvider {
    public function appId(): string;
    /** @return list<string> */
    public function supportedSubjectTypes(): array;
    public function collect(PersonalDataRequest $request): PersonalDataReport;
}
