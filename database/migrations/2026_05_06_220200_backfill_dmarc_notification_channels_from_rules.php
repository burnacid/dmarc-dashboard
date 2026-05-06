<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rules = DB::table('dmarc_alert_rules')
            ->select([
                'id',
                'user_id',
                'notification_email',
                'ntfy_url',
                'ntfy_token',
                'ntfy_ignore_certificate',
            ])
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (filled($rule->notification_email)) {
                $channelId = DB::table('dmarc_notification_channels')
                    ->where('user_id', $rule->user_id)
                    ->where('type', 'email')
                    ->where('email_to', $rule->notification_email)
                    ->value('id');

                if (! $channelId) {
                    $channelId = DB::table('dmarc_notification_channels')->insertGetId([
                        'user_id' => $rule->user_id,
                        'name' => 'Email: '.$rule->notification_email,
                        'type' => 'email',
                        'email_to' => $rule->notification_email,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('dmarc_alert_rule_notification_channel')->updateOrInsert([
                    'dmarc_alert_rule_id' => $rule->id,
                    'dmarc_notification_channel_id' => $channelId,
                ], [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

            if (filled($rule->ntfy_url)) {
                $channelId = DB::table('dmarc_notification_channels')
                    ->where('user_id', $rule->user_id)
                    ->where('type', 'ntfy')
                    ->where('ntfy_url', $rule->ntfy_url)
                    ->where('ntfy_token', $rule->ntfy_token)
                    ->where('ntfy_ignore_certificate', (bool) $rule->ntfy_ignore_certificate)
                    ->value('id');

                if (! $channelId) {
                    $channelId = DB::table('dmarc_notification_channels')->insertGetId([
                        'user_id' => $rule->user_id,
                        'name' => 'ntfy: '.$rule->ntfy_url,
                        'type' => 'ntfy',
                        'ntfy_url' => $rule->ntfy_url,
                        'ntfy_token' => $rule->ntfy_token,
                        'ntfy_ignore_certificate' => (bool) $rule->ntfy_ignore_certificate,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('dmarc_alert_rule_notification_channel')->updateOrInsert([
                    'dmarc_alert_rule_id' => $rule->id,
                    'dmarc_notification_channel_id' => $channelId,
                ], [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('dmarc_alert_rule_notification_channel')->truncate();
        DB::table('dmarc_notification_channels')->truncate();
    }
};

