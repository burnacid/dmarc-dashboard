<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class AccessScope
{
    /**
     * Whether the application is running in shared mode, where all
     * IMAP accounts, reports, alert rules, notification channels, and
     * DNS snapshots are visible to every authenticated user.
     */
    public static function sharedMode(): bool
    {
        return (bool) config('app.shared_mode', false);
    }

    /**
     * Scope a query builder to the given user's own rows, unless shared
     * mode is on, in which case no scoping is applied.
     */
    public static function ownedBy(Builder|QueryBuilder $query, User $user, string $column = 'user_id'): Builder|QueryBuilder
    {
        if (self::sharedMode()) {
            return $query;
        }

        return $query->where($column, $user->id);
    }

    /**
     * Same as ownedBy(), but for a whereHas(relation, ...) style
     * ownership constraint (e.g. DmarcReport -> ImapAccount -> user_id).
     */
    public static function ownedByViaRelation(Builder $query, User $user, string $relation, string $column = 'user_id'): Builder
    {
        if (self::sharedMode()) {
            return $query;
        }

        return $query->whereHas($relation, fn (Builder $q) => $q->where($column, $user->id));
    }

    /**
     * Whether $user may manage (update/delete) a resource owned by
     * $ownerId: either they own it, or they hold the elevated permission.
     */
    public static function canManage(User $user, ?int $ownerId, string $permission): bool
    {
        if ($ownerId !== null && $ownerId === $user->id) {
            return true;
        }

        return $user->can($permission);
    }
}
