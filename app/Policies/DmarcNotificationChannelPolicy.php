<?php

namespace App\Policies;

use App\Models\DmarcNotificationChannel;
use App\Models\User;
use App\Support\AccessScope;

class DmarcNotificationChannelPolicy
{
    public function update(User $user, DmarcNotificationChannel $dmarcNotificationChannel): bool
    {
        return AccessScope::canManage($user, $dmarcNotificationChannel->user_id, 'manage-notification-channels');
    }

    public function delete(User $user, DmarcNotificationChannel $dmarcNotificationChannel): bool
    {
        return $this->update($user, $dmarcNotificationChannel);
    }
}
