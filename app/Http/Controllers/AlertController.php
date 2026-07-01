<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAlertChannelsRequest;
use App\Http\Requests\UpdateAlertRulesRequest;
use App\Models\DmarcAlertRule;
use App\Models\DmarcNotificationChannel;
use App\Services\Dmarc\DkimFailRateSpikeAlertService;
use App\Services\Dmarc\DmarcAlertNotificationDispatcher;
use App\Services\Dmarc\SpfFailRateSpikeAlertService;
use App\Support\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $rules = AccessScope::ownedBy(DmarcAlertRule::query(), $user)
            ->with([
                'events' => fn ($q) => $q->latest('triggered_at')->limit(5),
                'notificationChannels',
            ])
            ->latest('id')
            ->paginate(20);

        $channels = AccessScope::ownedBy(DmarcNotificationChannel::query(), $user)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('alerts.index', [
            'rules' => $rules,
            'channels' => $channels,
        ]);
    }

    public function storeRule(UpdateAlertRulesRequest $request): RedirectResponse
    {
        $user = $request->user();

        $rule = new DmarcAlertRule([
            'user_id' => $user->id,
        ]);

        $this->fillRuleSettings($rule, $request);
        $rule->save();
        $rule->notificationChannels()->sync($request->validated('channel_ids', []));

        return to_route('alerts.index')->with('status', 'alert-rule-created');
    }

    public function updateRule(UpdateAlertRulesRequest $request, DmarcAlertRule $dmarcAlertRule): RedirectResponse
    {
        abort_unless($request->user()->can('update', $dmarcAlertRule), 404);

        $this->fillRuleSettings($dmarcAlertRule, $request);
        $dmarcAlertRule->save();
        $dmarcAlertRule->notificationChannels()->sync($request->validated('channel_ids', []));

        return to_route('alerts.index')->with('status', 'alert-rule-updated');
    }

    public function destroyRule(Request $request, DmarcAlertRule $dmarcAlertRule): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $dmarcAlertRule), 404);

        $dmarcAlertRule->delete();

        return to_route('alerts.index')->with('status', 'alert-rule-deleted');
    }

    public function channels(Request $request): View
    {
        $channels = AccessScope::ownedBy(DmarcNotificationChannel::query(), $request->user())
            ->orderByDesc('id')
            ->get();

        return view('alerts.channels', [
            'channels' => $channels,
        ]);
    }

    public function storeChannel(UpdateAlertChannelsRequest $request): RedirectResponse
    {
        $user = $request->user();

        $channel = new DmarcNotificationChannel([
            'user_id' => $user->id,
        ]);

        $this->fillChannelSettings($channel, $request);
        $channel->save();

        return to_route('alerts.channels')->with('status', 'alert-channel-created');
    }

    public function updateChannel(UpdateAlertChannelsRequest $request, DmarcNotificationChannel $dmarcNotificationChannel): RedirectResponse
    {
        abort_unless($request->user()->can('update', $dmarcNotificationChannel), 404);

        $this->fillChannelSettings($dmarcNotificationChannel, $request);
        $dmarcNotificationChannel->save();

        return to_route('alerts.channels')->with('status', 'alert-channel-updated');
    }

    public function destroyChannel(Request $request, DmarcNotificationChannel $dmarcNotificationChannel): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $dmarcNotificationChannel), 404);

        $dmarcNotificationChannel->delete();

        return to_route('alerts.channels')->with('status', 'alert-channel-deleted');
    }

    public function testFire(
        DmarcAlertRule $dmarcAlertRule,
        SpfFailRateSpikeAlertService $spfService,
        DkimFailRateSpikeAlertService $dkimService,
        DmarcAlertNotificationDispatcher $dispatcher,
        Request $request,
    ): RedirectResponse {
        abort_unless($request->user()->can('update', $dmarcAlertRule), 404);

        $now = \Carbon\Carbon::now();

        $payload = $dmarcAlertRule->metric === 'dkim_fail_rate_spike'
            ? $dkimService->evaluate($dmarcAlertRule, $now)
            : $spfService->evaluate($dmarcAlertRule, $now);

        // If threshold not met, synthesize a minimal payload so the test still fires.
        if ($payload === null) {
            if ($dmarcAlertRule->metric === 'dkim_fail_rate_spike') {
                $payload = [
                    'rule_id' => $dmarcAlertRule->id,
                    'metric' => $dmarcAlertRule->metric,
                    'domain' => $dmarcAlertRule->domain,
                    'window_start' => $now->copy()->subMinutes((int) $dmarcAlertRule->window_minutes),
                    'window_end' => $now->copy(),
                    'baseline_start' => $now->copy()->subDays((int) $dmarcAlertRule->baseline_days),
                    'baseline_end' => $now->copy()->subMinutes((int) $dmarcAlertRule->window_minutes),
                    'current_total_messages' => 0,
                    'current_dkim_fail_messages' => 0,
                    'current_fail_rate' => 0.0,
                    'baseline_total_messages' => 0,
                    'baseline_dkim_fail_messages' => 0,
                    'baseline_fail_rate' => 0.0,
                    'absolute_increase' => 0.0,
                ];
            } else {
                $payload = [
                    'rule_id' => $dmarcAlertRule->id,
                    'metric' => $dmarcAlertRule->metric,
                    'domain' => $dmarcAlertRule->domain,
                    'window_start' => $now->copy()->subMinutes((int) $dmarcAlertRule->window_minutes),
                    'window_end' => $now->copy(),
                    'baseline_start' => $now->copy()->subDays((int) $dmarcAlertRule->baseline_days),
                    'baseline_end' => $now->copy()->subMinutes((int) $dmarcAlertRule->window_minutes),
                    'current_total_messages' => 0,
                    'current_spf_fail_messages' => 0,
                    'current_fail_rate' => 0.0,
                    'baseline_total_messages' => 0,
                    'baseline_spf_fail_messages' => 0,
                    'baseline_fail_rate' => 0.0,
                    'absolute_increase' => 0.0,
                ];
            }
        }

        $dmarcAlertRule->loadMissing(['user', 'notificationChannels']);
        $dispatcher->dispatch($dmarcAlertRule, $payload);

        return redirect()->route('alerts.index')->with('status', 'test-fired');
    }

    private function fillRuleSettings(DmarcAlertRule $rule, UpdateAlertRulesRequest $request): void
    {
        $rule->forceFill([
            'name' => trim((string) $request->string('name')->toString()),
            'metric' => (string) $request->string('metric')->toString(),
            'domain' => $request->filled('domain')
                ? strtolower(trim((string) $request->string('domain')->toString()))
                : null,
            'threshold_multiplier' => (float) $request->input('threshold_multiplier', 2.0),
            'min_absolute_increase' => (float) $request->input('min_absolute_increase', 8.0),
            'min_messages' => (int) $request->integer('min_messages', 200),
            'window_minutes' => (int) $request->integer('window_minutes', 1440),
            'baseline_days' => (int) $request->integer('baseline_days', 14),
            'cooldown_minutes' => (int) $request->integer('cooldown_minutes', 720),
            'is_active' => $request->boolean('is_active', true),
        ]);
    }

    private function fillChannelSettings(DmarcNotificationChannel $channel, UpdateAlertChannelsRequest $request): void
    {
        $type = (string) $request->string('type')->toString();

        $channel->forceFill([
            'name' => trim((string) $request->string('name')->toString()),
            'type' => $type,
            'email_to' => $type === 'email'
                ? trim((string) $request->string('email_to')->toString())
                : null,
            'ntfy_url' => $type === 'ntfy' && $request->filled('ntfy_url')
                ? trim((string) $request->string('ntfy_url')->toString())
                : null,
            'ntfy_token' => $type === 'ntfy' && $request->filled('ntfy_token')
                ? trim((string) $request->string('ntfy_token')->toString())
                : null,
            'ntfy_ignore_certificate' => $type === 'ntfy'
                ? $request->boolean('ntfy_ignore_certificate')
                : false,
            'is_active' => $request->boolean('is_active', true),
        ]);
    }
}

