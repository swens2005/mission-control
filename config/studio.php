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

    // Used once, on 2026-11-03, to create Meagan's studio; empty since.
    'setup_token_sha256' => env('STUDIO_SETUP_TOKEN_SHA256', ''),

];
