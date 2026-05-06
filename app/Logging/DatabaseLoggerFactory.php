<?php

namespace App\Logging;

use Monolog\Logger;

class DatabaseLoggerFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __invoke(array $config): Logger
    {
        $level = Logger::toMonologLevel($config['level'] ?? 'debug');

        return new Logger('database', [
            new DatabaseLogHandler($level),
        ]);
    }
}

