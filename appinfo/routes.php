<?php

declare(strict_types=1);

return ['routes' => [
    ['name' => 'privacy_page#selfService', 'url' => '/privacy', 'verb' => 'GET'],
    ['name' => 'privacy_page#admin', 'url' => '/privacy/admin', 'verb' => 'GET'],
    ['name' => 'privacy#selfService', 'url' => '/api/privacy/self', 'verb' => 'GET'],
    ['name' => 'privacy#adminReport', 'url' => '/api/privacy/admin/{subjectUid}', 'verb' => 'GET'],
    ['name' => 'privacy#retentionPreview', 'url' => '/api/privacy/admin/{subjectUid}/retention-preview', 'verb' => 'GET'],
    ['name' => 'ad_suite_admin_api#settings', 'url' => '/api/ad-suite/admin/settings', 'verb' => 'GET'],
    ['name' => 'ad_suite_admin_api#saveCalendarContext', 'url' => '/api/ad-suite/admin/calendar-context', 'verb' => 'PUT'],
    ['name' => 'ad_suite_admin_api#saveOrganization', 'url' => '/api/ad-suite/admin/organization', 'verb' => 'PUT'],
    ['name' => 'ad_suite_admin_api#savePermissions', 'url' => '/api/ad-suite/admin/permissions', 'verb' => 'PUT'],
    ['name' => 'ad_suite_admin_api#saveLayout', 'url' => '/api/ad-suite/admin/layout', 'verb' => 'PUT'],
]];
