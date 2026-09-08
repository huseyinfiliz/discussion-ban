<?php

namespace HuseyinFiliz\DiscussionBan\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class DiscussionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('huseyinfiliz-discussion-ban');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'email' => 'mod@machine.local', 'is_email_confirmed' => 1],
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
            Discussion::class => array_map(fn ($id) => [
                'id' => $id,
                'title' => "Discussion {$id}",
                'user_id' => 1,
                'first_post_id' => $id,
                'comment_count' => 1,
                'created_at' => '2026-01-01 00:00:00',
            ], range(1, 10)),
        ]);
    }

    public function test_listing_discussions_as_moderator_does_not_trigger_n_plus_one()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}