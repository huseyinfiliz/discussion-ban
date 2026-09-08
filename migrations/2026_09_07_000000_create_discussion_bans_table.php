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
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'discussion_bans',
    function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('discussion_id');
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('banned_by_id')->nullable();
        $table->text('reason')->nullable();
        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('revoked_at')->nullable();
        $table->unsignedInteger('revoked_by_id')->nullable();

        $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('cascade');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('banned_by_id')->references('id')->on('users')->onDelete('set null');
        $table->foreign('revoked_by_id')->references('id')->on('users')->onDelete('set null');

        $table->index(['discussion_id', 'user_id', 'revoked_at']);
    }
);