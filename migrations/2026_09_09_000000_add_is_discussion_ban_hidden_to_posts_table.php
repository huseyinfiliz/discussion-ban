<?php

/*
 * This file is part of huseyinfiliz/discussion-ban.
 *
 * Copyright (c) 2026 Hüseyin Filiz.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('posts', [
    'is_discussion_ban_hidden' => ['boolean', 'default' => false],
]);
