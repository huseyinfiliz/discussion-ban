<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->unsignedInteger('discussion_ban_id')->nullable();
        });

        // A legacy boolean cannot prove that a later staff hide still belongs to a ban.
        // Preserve the hidden content, but relinquish automatic restoration authority.
        $schema->getConnection()->table('posts')
            ->where('is_discussion_ban_hidden', true)
            ->update(['is_discussion_ban_hidden' => false]);
    },
    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->dropColumn('discussion_ban_id');
        });
    },
];
