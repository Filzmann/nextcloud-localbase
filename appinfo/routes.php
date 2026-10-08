<?php

declare(strict_types=1);

return ['routes' => [
    ['name' => 'privacy_page#selfService', 'url' => '/privacy', 'verb' => 'GET'],
    ['name' => 'privacy_page#admin', 'url' => '/privacy/admin', 'verb' => 'GET'],
    ['name' => 'privacy#selfService', 'url' => '/api/privacy/self', 'verb' => 'GET'],
    ['name' => 'privacy#adminReport', 'url' => '/api/privacy/admin/{subjectUid}', 'verb' => 'GET'],
    ['name' => 'flz_suite_admin_api#settings', 'url' => '/api/flz-full-suite/admin/settings', 'verb' => 'GET'],
    ['name' => 'flz_suite_admin_api#saveCalendarContext', 'url' => '/api/flz-full-suite/admin/calendar-context', 'verb' => 'PUT'],
    ['name' => 'flz_suite_admin_api#saveOrganization', 'url' => '/api/flz-full-suite/admin/organization', 'verb' => 'PUT'],
    ['name' => 'flz_suite_admin_api#savePermissions', 'url' => '/api/flz-full-suite/admin/permissions', 'verb' => 'PUT'],
    ['name' => 'flz_suite_admin_api#saveLayout', 'url' => '/api/flz-full-suite/admin/layout', 'verb' => 'PUT'],
    ['name' => 'flz_suite_admin_api#resetLayout', 'url' => '/api/flz-full-suite/admin/layout', 'verb' => 'DELETE'],
]];
