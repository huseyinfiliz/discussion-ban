<?php

namespace HuseyinFiliz\DiscussionBan\Tests\integration\api;

use Flarum\Audit\AuditLog;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use HuseyinFiliz\DiscussionBan\DiscussionBan;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

class DiscussionBanSecurityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'huseyinfiliz-discussion-ban');
        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'delegate', 'email' => 'delegate@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'target', 'email' => 'target@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'staff', 'email' => 'staff@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'hiddenonly', 'email' => 'hidden@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Delegate', 'name_plural' => 'Delegates'],
                ['id' => 11, 'name_singular' => 'Staff', 'name_plural' => 'Staff'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
                ['user_id' => 5, 'group_id' => 11],
            ],
            'group_permission' => [
                ['group_id' => 10, 'permission' => 'discussion.banUsers'],
                ['group_id' => 11, 'permission' => 'discussion.hidePosts'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Visible discussion', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 3],
                ['id' => 2, 'title' => 'Hidden discussion', 'user_id' => 1, 'comment_count' => 2, 'hidden_at' => '2026-01-01 00:00:00', 'hidden_user_id' => 1],
                ['id' => 3, 'title' => 'Restricted discussion', 'user_id' => 1, 'comment_count' => 2],
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'Restricted', 'slug' => 'restricted', 'is_restricted' => true],
                ['id' => 2, 'name' => 'Public', 'slug' => 'public', 'is_restricted' => false],
            ],
            'discussion_tag' => [
                ['discussion_id' => 3, 'tag_id' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'number' => 1, 'type' => 'comment', 'content' => '<t>First</t>', 'created_at' => '2026-01-01 00:00:00'],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 4, 'number' => 2, 'type' => 'comment', 'content' => '<t>Target</t>', 'created_at' => '2026-01-01 00:00:00'],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 4, 'number' => 3, 'type' => 'comment', 'content' => '<t>Preexisting staff hide</t>', 'hidden_at' => '2026-01-01 01:00:00', 'hidden_user_id' => 5, 'created_at' => '2026-01-01 00:00:00'],
                ['id' => 4, 'discussion_id' => 1, 'user_id' => 4, 'number' => 4, 'type' => 'comment', 'content' => '<t>Ban-owned hide</t>', 'created_at' => '2026-01-01 00:00:00'],
                ['id' => 5, 'discussion_id' => 1, 'user_id' => 6, 'number' => 5, 'type' => 'comment', 'content' => '<t>Hidden participant</t>', 'hidden_at' => '2026-01-01 01:00:00', 'hidden_user_id' => 5, 'created_at' => '2026-01-01 00:00:00'],
                ['id' => 6, 'discussion_id' => 2, 'user_id' => 6, 'number' => 1, 'type' => 'comment', 'content' => '<t>Hidden discussion participant</t>', 'created_at' => '2026-01-01 00:00:00'],
            ],
            DiscussionBan::class => [
                ['id' => 1, 'discussion_id' => 2, 'user_id' => 4, 'banned_by_id' => 1, 'reason' => 'Private moderation reason', 'created_at' => '2026-01-01 00:00:00'],
                ['id' => 2, 'discussion_id' => 3, 'user_id' => 4, 'banned_by_id' => 1, 'created_at' => '2026-01-01 00:00:00'],
            ],
        ]);
    }

    protected function ban(int $discussion = 1, int $target = 4, int $actor = 3): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/discussion-bans', [
            'authenticatedAs' => $actor,
            'json' => ['data' => [
                'type' => 'discussion-bans',
                'attributes' => ['reason' => 'Security regression fixture'],
                'relationships' => [
                    'discussion' => ['data' => ['type' => 'discussions', 'id' => (string) $discussion]],
                    'user' => ['data' => ['type' => 'users', 'id' => (string) $target]],
                ],
            ]],
        ]));
    }

    protected function getAs(string $path, int $actor = 3): ResponseInterface
    {
        parse_str(parse_url($path, PHP_URL_QUERY) ?? '', $query);

        return $this->send($this->request('GET', $path, ['authenticatedAs' => $actor])->withQueryParams($query));
    }

    protected function revoke(int $ban, int $actor = 3): ResponseInterface
    {
        return $this->send($this->request('DELETE', '/api/discussion-bans/'.$ban, ['authenticatedAs' => $actor]));
    }

    public function test_hidden_discussion_cannot_be_read_or_moderated_by_ban_only_delegate(): void
    {
        $this->assertSame(404, $this->getAs('/api/discussions/2')->getStatusCode());
        $this->assertSame(404, $this->getAs('/api/users/discussion-participants/2')->getStatusCode());
        $this->assertSame(404, $this->getAs('/api/discussion-bans?filter[discussion]=2')->getStatusCode());
        $this->assertSame(404, $this->revoke(1)->getStatusCode());
        $this->assertSame(404, $this->ban(2, 6)->getStatusCode());
        $this->assertNull(DiscussionBan::findOrFail(1)->revoked_at);
    }

    public function test_restricted_tag_remains_inaccessible_to_global_ban_delegate(): void
    {
        $this->assertSame(404, $this->getAs('/api/discussions/3')->getStatusCode());
        $this->assertSame(404, $this->getAs('/api/users/discussion-participants/3')->getStatusCode());
        $this->assertSame(404, $this->getAs('/api/discussion-bans?filter[discussion]=3')->getStatusCode());
        $this->assertSame(404, $this->revoke(2)->getStatusCode());
    }

    public function test_tag_action_denial_is_preserved_when_discussion_is_visible(): void
    {
        $this->database()->table('group_permission')->insert(['group_id' => 10, 'permission' => 'tag1.viewForum']);

        $this->assertSame(200, $this->getAs('/api/discussions/3')->getStatusCode());
        $this->assertSame(403, $this->getAs('/api/users/discussion-participants/3')->getStatusCode());
        $this->assertSame(403, $this->getAs('/api/discussion-bans?filter[discussion]=3')->getStatusCode());
        $this->assertSame(403, $this->revoke(2)->getStatusCode());
    }

    public function test_participants_are_selected_only_from_visible_posts(): void
    {
        $response = $this->getAs('/api/users/discussion-participants/1');
        $this->assertSame(200, $response->getStatusCode());
        $ids = array_column(json_decode((string) $response->getBody(), true)['data'], 'id');
        $this->assertContains('4', $ids);
        $this->assertNotContains('6', $ids);
    }

    public function test_ordinary_member_cannot_access_moderation_endpoints(): void
    {
        $this->assertSame(403, $this->getAs('/api/users/discussion-participants/1', 2)->getStatusCode());
        $this->assertSame(403, $this->getAs('/api/discussion-bans?filter[discussion]=1', 2)->getStatusCode());
        $this->assertSame(403, $this->getAs('/api/discussion-bans', 3)->getStatusCode());
        $this->assertSame(403, $this->ban(actor: 2)->getStatusCode());
    }

    public static function visibilitySettings(): array
    {
        return ['hidden' => [true], 'read-only' => [false]];
    }

    #[DataProvider('visibilitySettings')]
    public function test_banned_delegate_cannot_unban_itself_or_moderate_others(bool $hide): void
    {
        $this->setting('huseyinfiliz-discussion-ban.hideDiscussionsFromBanned', $hide);
        $this->assertSame(201, $this->ban(target: 3, actor: 1)->getStatusCode());
        $ban = DiscussionBan::activeBan(1, 3);
        $this->assertNotNull($ban);

        $this->assertSame($hide ? 404 : 200, $this->getAs('/api/discussions/1')->getStatusCode());
        $expected = $hide ? 404 : 403;
        $this->assertSame($expected, $this->getAs('/api/users/discussion-participants/1')->getStatusCode());
        $this->assertSame($expected, $this->getAs('/api/discussion-bans?filter[discussion]=1')->getStatusCode());
        $this->assertSame($expected, $this->revoke($ban->id)->getStatusCode());
        $this->assertSame($expected, $this->ban()->getStatusCode());
        $this->assertNull($ban->fresh()->revoked_at);
        $this->assertSame(204, $this->revoke($ban->id, 1)->getStatusCode());
    }

    public function test_unban_preserves_independent_restore_and_rehide(): void
    {
        $this->assertSame(201, $this->ban()->getStatusCode());
        $ban = DiscussionBan::activeBan(1, 4);
        $this->assertNotNull($ban);
        $this->assertSame($ban->id, (int) Post::findOrFail(2)->discussion_ban_id);

        $this->assertSame(404, $this->send($this->request('PATCH', '/api/posts/2', [
            'authenticatedAs' => 3,
            'json' => ['data' => ['type' => 'posts', 'id' => '2', 'attributes' => ['isHidden' => false]]],
        ]))->getStatusCode());

        foreach ([false, true] as $isHidden) {
            $response = $this->send($this->request('PATCH', '/api/posts/2', [
                'authenticatedAs' => 5,
                'json' => ['data' => ['type' => 'posts', 'id' => '2', 'attributes' => ['isHidden' => $isHidden]]],
            ]));
            $this->assertSame(200, $response->getStatusCode());
            $this->assertFalse((bool) Post::findOrFail(2)->is_discussion_ban_hidden);
            $this->assertNull(Post::findOrFail(2)->discussion_ban_id);
        }

        $this->assertSame(5, (int) Post::findOrFail(2)->hidden_user_id);
        $this->assertSame(404, $this->getAs('/api/posts/2', 2)->getStatusCode());
        $this->assertSame(204, $this->revoke($ban->id)->getStatusCode());
        $this->assertNotNull(Post::findOrFail(2)->hidden_at);
        $this->assertSame(5, (int) Post::findOrFail(2)->hidden_user_id);
        $this->assertSame(404, $this->getAs('/api/posts/2', 2)->getStatusCode());
        $this->assertNotNull(Post::findOrFail(3)->hidden_at);
        $this->assertNull(Post::findOrFail(4)->hidden_at);
        $this->assertSame(200, $this->getAs('/api/posts/4', 2)->getStatusCode());
    }

    public function test_unban_does_not_trust_a_stale_or_unattributed_marker(): void
    {
        $this->assertSame(201, $this->ban()->getStatusCode());
        $ban = DiscussionBan::activeBan(1, 4);
        $this->database()->table('posts')->where('id', 2)->update(['discussion_ban_id' => null]);

        $this->assertSame(204, $this->revoke($ban->id)->getStatusCode());
        $this->assertNotNull(Post::findOrFail(2)->hidden_at);
        $this->assertNull(Post::findOrFail(4)->hidden_at);
    }

    public function test_audit_records_ban_and_unban_with_actor_and_identifiers(): void
    {
        $this->extension('flarum-audit');
        $this->assertSame(201, $this->ban()->getStatusCode());
        $ban = DiscussionBan::activeBan(1, 4);
        $this->assertSame(204, $this->revoke($ban->id)->getStatusCode());

        foreach (['discussion_ban.banned', 'discussion_ban.unbanned'] as $action) {
            $records = AuditLog::where('action', $action)->get();
            $this->assertCount(1, $records);
            $this->assertSame(3, (int) $records[0]->actor_id);
            $payload = $records[0]->payload;
            $this->assertSame($ban->id, $payload['ban_id']);
            $this->assertSame(1, $payload['discussion_id']);
            $this->assertSame(4, $payload['user_id']);
            if ($action === 'discussion_ban.banned') {
                $this->assertSame('Security regression fixture', $payload['reason']);
            }
        }
    }
}
