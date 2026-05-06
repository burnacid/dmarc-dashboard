<?php

namespace App\Logging;

use Illuminate\Support\Facades\DB;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Throwable;

class DatabaseLogHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel' => $record->channel,
                'level' => $record->level->getName(),
                'level_value' => $record->level->value,
                'message' => $record->message,
                'context' => $record->context === [] ? null : json_encode($record->context, JSON_UNESCAPED_SLASHES),
                'extra' => $record->extra === [] ? null : json_encode($record->extra, JSON_UNESCAPED_SLASHES),
                'logged_at' => $record->datetime->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable) {
            // Avoid recursive logging failure loops if DB is unavailable.
        }
    }
}

