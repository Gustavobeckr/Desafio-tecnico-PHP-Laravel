<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sem isto, uma URL que não casa com nenhum Http::fake() sai para a rede
        // de verdade e o teste passa pelo motivo errado.
        Http::preventStrayRequests();
    }
}
