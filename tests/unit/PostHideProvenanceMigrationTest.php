<?php

namespace HuseyinFiliz\DiscussionBan\Tests\unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use PHPUnit\Framework\TestCase;

class PostHideProvenanceMigrationTest extends TestCase
{
    public function test_upgrade_preserves_legacy_hides_without_trusting_their_markers(): void
    {
        $db = new SQLiteConnection(new \PDO('sqlite::memory:'), ':memory:', 'migration_');
        $schema = $db->getSchemaBuilder();
        $schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('hidden_at')->nullable();
            $table->unsignedInteger('hidden_user_id')->nullable();
            $table->boolean('is_discussion_ban_hidden')->default(false);
        });
        $db->table('posts')->insert([
            ['id' => 1, 'hidden_at' => '2026-11-01 12:00:00', 'hidden_user_id' => 7, 'is_discussion_ban_hidden' => true],
            ['id' => 2, 'hidden_at' => '2026-11-02 12:00:00', 'hidden_user_id' => 8, 'is_discussion_ban_hidden' => false],
            ['id' => 3, 'hidden_at' => null, 'hidden_user_id' => null, 'is_discussion_ban_hidden' => false],
        ]);
        $before = $db->table('posts')->orderBy('id')->get(['id', 'hidden_at', 'hidden_user_id'])->toArray();

        $migration = require __DIR__.'/../../migrations/2026_11_18_000000_track_post_hide_provenance.php';
        $migration['up']($schema);

        $this->assertTrue($schema->hasColumn('posts', 'discussion_ban_id'));
        $this->assertEquals($before, $db->table('posts')->orderBy('id')->get(['id', 'hidden_at', 'hidden_user_id'])->toArray());
        $this->assertSame(0, $db->table('posts')->where('is_discussion_ban_hidden', true)->count());
        $this->assertSame(0, $db->table('posts')->whereNotNull('discussion_ban_id')->count());

        $migration['down']($schema);

        $this->assertFalse($schema->hasColumn('posts', 'discussion_ban_id'));
        $this->assertEquals($before, $db->table('posts')->orderBy('id')->get(['id', 'hidden_at', 'hidden_user_id'])->toArray());
    }
}
