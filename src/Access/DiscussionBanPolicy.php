<?php

namespace HuseyinFiliz\DiscussionBan\Access;

use Flarum\Discussion\Discussion;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use HuseyinFiliz\DiscussionBan\DiscussionBan;

class DiscussionBanPolicy extends AbstractPolicy
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {}

    public function reply(User $actor, Discussion $discussion): ?string
    {
        if ($actor->id && DiscussionBan::isUserBanned($discussion->id, $actor->id)) {
            return static::FORCE_DENY;
        }

        return null;
    }

    public function view(User $actor, Discussion $discussion): ?string
    {
        $hideDiscussions = (bool) $this->settings->get('huseyinfiliz-discussion-ban.hideDiscussionsFromBanned', true);

        if ($hideDiscussions && $actor->id && DiscussionBan::isUserBanned($discussion->id, $actor->id)) {
            return static::FORCE_DENY;
        }

        return null;
    }

    public function banUsers(User $actor, Discussion $discussion): ?string
    {
        if ($actor->hasPermission('discussion.banUsers')) {
            return static::ALLOW;
        }

        return null;
    }
}