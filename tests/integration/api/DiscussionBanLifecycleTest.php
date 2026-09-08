<?php

namespace HuseyinFiliz\DiscussionBan\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use HuseyinFiliz\DiscussionBan\DiscussionBan;

class DiscussionBanLifecycleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('huseyinfiliz-discussion-ban');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id: 2
                ['id' => 3, 'username' => 'moderator', 'email' => 'mod@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'baduser', 'email' => 'bad@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 4, 'name_singular' => 'Mod', 'name_plural' => 'Mods'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.banUsers'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test Discussion', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First post</p></t>', 'created_at' => '2026-01-01 00:00:00'],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Bad user post</p></t>', 'hidden_at' => null, 'created_at' => '2026-01-01 00:00:00'],
            ],
        ]);
    }

    public function test_moderator_can_ban_user_and_hides_their_posts()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussion-bans', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'discussion-bans',
                        'attributes' => [
                            'reason' => 'Spamming',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => '1']],
                            'user' => ['data' => ['type' => 'users', 'id' => '4']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $ban = DiscussionBan::activeBan(1, 4);
        $this->assertNotNull($ban);
        $this->assertEquals('Spamming', $ban->reason);

        $post = Post::find(2);
        $this->assertNotNull($post->hidden_at);

        $replyResponseDefault = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 4,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => ['content' => 'Another reply'],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => '1']],
                        ],
                    ],
                ],
            ])
        );
        $this->assertEquals(404, $replyResponseDefault->getStatusCode());

        /** @var SettingsRepositoryInterface $settings */
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('huseyinfiliz-discussion-ban.hideDiscussionsFromBanned', false);

        $replyResponseVisible = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 4,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => ['content' => 'Another reply'],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => '1']],
                        ],
                    ],
                ],
            ])
        );
        $this->assertEquals(403, $replyResponseVisible->getStatusCode());

        $deleteResponse = $this->send(
            $this->request('DELETE', "/api/discussion-bans/{$ban->id}", [
                'authenticatedAs' => 3,
            ])
        );
        $this->assertEquals(204, $deleteResponse->getStatusCode());

        $post->refresh();
        $this->assertNull($post->hidden_at);
    }

    public function test_moderator_cannot_ban_administrator()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussion-bans', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'discussion-bans',
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => '1']],
                            'user' => ['data' => ['type' => 'users', 'id' => '1']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }
}