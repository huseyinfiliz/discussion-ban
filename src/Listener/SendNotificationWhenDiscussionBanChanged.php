<?php

/*
 * This file is part of huseyinfiliz/discussion-ban.
 *
 * Copyright (c) 2026 Hüseyin Filiz.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace HuseyinFiliz\DiscussionBan\Listener;

use Flarum\Notification\NotificationSyncer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use HuseyinFiliz\DiscussionBan\Event;
use HuseyinFiliz\DiscussionBan\Notification\DiscussionBannedBlueprint;
use HuseyinFiliz\DiscussionBan\Notification\DiscussionUnbannedBlueprint;

class SendNotificationWhenDiscussionBanChanged
{
    public function __construct(
        protected NotificationSyncer $notifications,
        protected SettingsRepositoryInterface $settings
    ) {}

    public function handle(Event\Banned|Event\Unbanned $event): void
    {
        if ($event instanceof Event\Banned) {
            $this->handleBanned($event);
        } elseif ($event instanceof Event\Unbanned) {
            $this->handleUnbanned($event);
        }
    }

    public function handleBanned(Event\Banned $event): void
    {
        if (! $this->settings->get('huseyinfiliz-discussion-ban.sendNotifications', false)) {
            return;
        }

        /** @var User|null $targetUser */
        $targetUser = $event->ban->user ?: User::find($event->ban->user_id);
        if ($targetUser && (int) $targetUser->id !== (int) $event->actor->id) {
            $this->notifications->sync(
                new DiscussionBannedBlueprint($event->ban, $event->actor),
                [$targetUser]
            );
        }
    }

    public function handleUnbanned(Event\Unbanned $event): void
    {
        if (! $this->settings->get('huseyinfiliz-discussion-ban.sendNotifications', false)) {
            return;
        }

        /** @var User|null $targetUser */
        $targetUser = $event->ban->user ?: User::find($event->ban->user_id);
        if ($targetUser && (int) $targetUser->id !== (int) $event->actor->id) {
            $this->notifications->sync(
                new DiscussionUnbannedBlueprint($event->ban, $event->actor),
                [$targetUser]
            );
        }
    }
}
