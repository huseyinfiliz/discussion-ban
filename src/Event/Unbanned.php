<?php

namespace HuseyinFiliz\DiscussionBan\Event;

use Flarum\User\User;
use HuseyinFiliz\DiscussionBan\DiscussionBan;

class Unbanned
{
    public function __construct(
        public DiscussionBan $ban,
        public User $actor
    ) {}
}