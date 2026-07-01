<?php

namespace App\Policies;

use App\Models\DmarcAlertRule;
use App\Models\User;
use App\Support\AccessScope;

class DmarcAlertRulePolicy
{
    public function update(User $user, DmarcAlertRule $dmarcAlertRule): bool
    {
        return AccessScope::canManage($user, $dmarcAlertRule->user_id, 'manage-alert-rules');
    }

    public function delete(User $user, DmarcAlertRule $dmarcAlertRule): bool
    {
        return $this->update($user, $dmarcAlertRule);
    }
}
