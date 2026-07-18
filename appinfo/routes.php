<?php

declare(strict_types=1);

return ['routes' => [
    ['name' => 'ad_suite_admin_api#settings', 'url' => '/api/ad-suite/admin/settings', 'verb' => 'GET'],
    ['name' => 'ad_suite_admin_api#saveOrganization', 'url' => '/api/ad-suite/admin/organization', 'verb' => 'PUT'],
    ['name' => 'ad_suite_admin_api#savePermissions', 'url' => '/api/ad-suite/admin/permissions', 'verb' => 'PUT'],
    ['name' => 'ad_suite_admin_api#saveLayout', 'url' => '/api/ad-suite/admin/layout', 'verb' => 'PUT'],
]];
