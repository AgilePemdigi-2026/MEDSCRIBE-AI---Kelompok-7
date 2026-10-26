<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests never call an external provider, regardless of the developer's .env.
        config(['medscribe.soap_driver' => 'mock']);
    }
}
