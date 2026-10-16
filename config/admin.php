<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Account Management Networks
    |--------------------------------------------------------------------------
    |
    | Account creation and management is done on our side only, from the
    | office network. Requests to those routes are refused unless the
    | connecting address falls in one of these comma-separated IPs or CIDR
    | ranges. Defaults to loopback plus the private LAN ranges.
    |
    | If the web server proxies to Laravel over loopback (proxy_pass to
    | 127.0.0.1), every public request arrives from 127.0.0.1 — drop the
    | loopback entries there and list only the office subnet.
    |
    */

    'allowed_networks' => array_values(array_filter(array_map('trim', explode(',', env(
        'ADMIN_ALLOWED_NETWORKS',
        '127.0.0.1,::1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'
    ))))),

    /*
    |--------------------------------------------------------------------------
    | Account Management Roles
    |--------------------------------------------------------------------------
    |
    | users.level values allowed to manage accounts.
    |
    */

    'manager_roles' => array_values(array_filter(array_map('trim', explode(',', env(
        'ADMIN_MANAGER_ROLES',
        'superadmin'
    ))))),

];
