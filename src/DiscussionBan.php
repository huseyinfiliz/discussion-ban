<?php

namespace HuseyinFiliz\DiscussionBan;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $discussion_id
 * @property int $user_id
 * @property int|null $banned_by_id
 * @property string|null $reason
 * @property Carbon $created_at
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by_id
 * @property Discussion $discussion
 * @property User $user
 * @property User|null $bannedBy
 * @property User|null $revokedBy
 */
class DiscussionBan extends AbstractModel
{
    protected $table = 'discussion_bans';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /** @var array<int, array<int, true>> */
    protected static array $userBansCache = [];

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public static function activeBan(int $discussionId, int $userId): ?self
    {
        return static::query()
            ->where('discussion_id', $discussionId)
            ->where('user_id', $userId)
            ->active()
            ->first();
    }

    public static function isUserBanned(int $discussionId, int $userId): bool
    {
        if (! isset(static::$userBansCache[$userId])) {
            $discussionIds = static::query()
                ->where('user_id', $userId)
                ->active()
                ->pluck('discussion_id')
                ->all();

            static::$userBansCache[$userId] = array_fill_keys($discussionIds, true);
        }

        return isset(static::$userBansCache[$userId][$discussionId]);
    }

    public static function resetCache(): void
    {
        static::$userBansCache = [];
    }
}