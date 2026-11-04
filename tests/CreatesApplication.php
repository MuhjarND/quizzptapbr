<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');
        if ($connection !== 'sqlite' && $database !== 'quiz_arena_test') {
            throw new \RuntimeException('Test SQL hanya boleh memakai database quiz_arena_test.');
        }

        return $app;
    }
}
