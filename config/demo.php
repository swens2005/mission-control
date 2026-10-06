<?php

/*
|--------------------------------------------------------------------------
| Public demo ("Take the controls")
|--------------------------------------------------------------------------
|
| Each visitor gets their own sandbox workspace (ADR 0004), seeded from
| App\Support\Sandbox\DemoTemplate and deleted after it expires by the
| hourly sandbox:prune command.
|
*/

return [

    'enabled' => (bool) env('DEMO_ENABLED', true),

    'lifetime_hours' => (int) env('DEMO_LIFETIME_HOURS', 24),

    // Protects the shared host's database and disk.
    'max_active' => (int) env('DEMO_MAX_ACTIVE', 200),

    'per_ip_per_hour' => (int) env('DEMO_PER_IP_PER_HOUR', 5),

    // Sandbox logins look like demo-ab12cd34@sandbox.codelaunch.nl. No mail
    // is ever sent to them.
    'email_domain' => env('DEMO_EMAIL_DOMAIN', 'sandbox.codelaunch.nl'),

    // Proofmark uploads (ADR 0009): per sandbox, and for all sandboxes
    // together, so the demo can't fill the host's disk.
    'upload_quota_bytes' => (int) env('DEMO_UPLOAD_QUOTA_BYTES', 20 * 1024 * 1024),

    'total_upload_quota_bytes' => (int) env('DEMO_TOTAL_UPLOAD_QUOTA_BYTES', 1024 * 1024 * 1024),

    // The only sites a sandbox's Launch Control may check (ADR 0007).
    'check_hosts' => ['codelaunch.nl', 'www.codelaunch.nl'],

];
