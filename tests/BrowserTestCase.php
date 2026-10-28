<?php

namespace Tests;

/**
 * Browser tests (story 33) need the real, built front end: run
 * `npm run build` first.
 */
abstract class BrowserTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withVite();
    }
}
