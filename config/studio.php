<?php

return [

    /*
    |--------------------------------------------------------------------------
    | One-time studio setup (story 41, ADR 0013)
    |--------------------------------------------------------------------------
    |
    | The SHA-256 fingerprint of a random token Meagan made on her own PC.
    | Only the fingerprint lives here; the token itself is typed into the
    | setup page once. Empty means setup is closed. Setup also closes for good
    | as soon as a real (non-sandbox) studio exists, so this is removed again
    | afterwards.
    |
    */

    // Meagan's token, set 2026-11-03; remove once her studio exists.
    'setup_token_sha256' => env('STUDIO_SETUP_TOKEN_SHA256', 'ea3455508609d19291a5a9fba8cbba389b0b6813f946f709545d79a8536a15a2'),

];
