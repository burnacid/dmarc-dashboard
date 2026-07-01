<?php

namespace App\Policies;

use App\Models\ImapAccount;
use App\Models\User;
use App\Support\AccessScope;

class ImapAccountPolicy
{
    public function update(User $user, ImapAccount $imapAccount): bool
    {
        return AccessScope::canManage($user, $imapAccount->user_id, 'manage-imap-accounts');
    }

    public function delete(User $user, ImapAccount $imapAccount): bool
    {
        return $this->update($user, $imapAccount);
    }
}
