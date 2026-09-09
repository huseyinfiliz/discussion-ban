<?php

/*
 * This file is part of huseyinfiliz/discussion-ban.
 *
 * Copyright (c) 2026 Hüseyin Filiz.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace HuseyinFiliz\DiscussionBan\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use HuseyinFiliz\DiscussionBan\DiscussionBan;

class DiscussionBannedBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(
        public DiscussionBan $ban,
        public User $actor
    ) {}

    public function getSubject(): ?AbstractModel
    {
        return $this->ban->discussion ?: Discussion::find($this->ban->discussion_id);
    }

    public function getFromUser(): ?User
    {
        return $this->actor;
    }

    public function getData(): array
    {
        return [
            'reason' => $this->ban->reason,
        ];
    }

    public static function getType(): string
    {
        return 'discussionBanned';
    }

    public static function getSubjectModel(): string
    {
        return Discussion::class;
    }
}
