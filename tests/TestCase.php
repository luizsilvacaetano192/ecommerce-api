<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Tests\CreatesApplication; 

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
       
        parent::setUp();
        
        if (env('APP_ENV') === 'testing') {
            
            $this->configureDatabaseForTesting();
        }
    }

    /**
     * Configura o banco de dados para testes
     */
    protected function configureDatabaseForTesting()
    {
        // Configuração para SQLite em memória
        if (config('database.default') === 'sqlite') {
            config([
                'database.connections.sqlite.database' => ':memory:'
            ]);
        }

        // Executa migrations se necessário
        Artisan::call('migrate:fresh');
    }

    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown(): void
    {
        if (env('APP_ENV') === 'testing') {
            Artisan::call('migrate:reset');
        }
        
        parent::tearDown();
    }
}