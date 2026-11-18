<?php

namespace HuseyinFiliz\DiscussionBan\Listener;

use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Restored;

class ClearBanHideProvenance
{
    public function handle(Hidden|Restored $event): void
    {
        $post = $event->post;

        if ($post->is_discussion_ban_hidden || $post->discussion_ban_id !== null) {
            $post->is_discussion_ban_hidden = false;
            $post->discussion_ban_id = null;
            $post->saveQuietly();
        }
    }
}
