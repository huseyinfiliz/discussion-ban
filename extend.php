<?php

namespace HuseyinFiliz\DiscussionBan;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Serializer;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use HuseyinFiliz\DiscussionBan\Access\DiscussionBanPolicy;
use HuseyinFiliz\DiscussionBan\Api\Resource\DiscussionBanResource;
use HuseyinFiliz\DiscussionBan\Event;
use Illuminate\Database\Eloquent\Builder;

use function Tobyz\JsonApiServer\json_api_response;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Policy())
        ->modelPolicy(Discussion::class, DiscussionBanPolicy::class),

    (new Extend\ModelVisibility(Discussion::class))
        ->scope(function (User $actor, Builder $query) {
            $settings = resolve(SettingsRepositoryInterface::class);

            if (! $actor->id || ! $settings->get('huseyinfiliz-discussion-ban.hideDiscussionsFromBanned', true)) {
                return;
            }

            $query->whereNotIn('discussions.id', function ($sub) use ($actor) {
                $sub->select('discussion_id')
                    ->from('discussion_bans')
                    ->where('user_id', $actor->id)
                    ->whereNull('revoked_at');
            });
        }),

    new Extend\ApiResource(DiscussionBanResource::class),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canBanUsers')
                ->get(fn (Discussion $discussion, Context $context) => (bool) $context->getActor()->can('banUsers', $discussion)),

            Schema\Arr::make('bannedUserMap')
                ->visible(function (Discussion $discussion, Context $context) {
                    if (! $context->getActor()->can('banUsers', $discussion)) {
                        return false;
                    }

                    $routeParams = $context->request->getAttribute('routeParameters') ?? [];

                    return ! empty($routeParams['id']);
                })
                ->get(function (Discussion $discussion) {
                    $bans = DiscussionBan::where('discussion_id', $discussion->id)
                        ->whereNull('revoked_at')
                        ->pluck('id', 'user_id');

                    $map = [];
                    foreach ($bans as $userId => $banId) {
                        $map[(string) $userId] = (int) $banId;
                    }

                    return $map;
                }),
        ]),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->endpoints(fn () => [
            Endpoint\Endpoint::make('discussion-participants')
                ->route('GET', '/discussion-participants/{discussionId}')
                ->action(function (Context $context) {
                    $actor = $context->getActor();

                    $discussionId = $context->request->getAttribute('routeParameters')['discussionId'];
                    $discussion = Discussion::findOrFail($discussionId);

                    $actor->assertCan('banUsers', $discussion);

                    $q = $context->request->getQueryParams()['filter']['q'] ?? null;

                    $userIds = Post::where('discussion_id', $discussion->id)
                        ->whereNotNull('user_id')
                        ->distinct()
                        ->pluck('user_id');

                    $bannedUserIds = DiscussionBan::where('discussion_id', $discussion->id)
                        ->active()
                        ->pluck('user_id');

                    $query = User::whereIn('id', $userIds)
                        ->whereNotIn('id', $bannedUserIds)
                        ->where('id', '!=', $actor->id);

                    if ($q) {
                        $query->where('username', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%');
                    }

                    return $query->orderBy('username')->limit(20)->get();
                })
                ->response(function (Context $context, $data) {
                    $serializer = new Serializer($context);
                    $userResource = $context->api->getResource('users');

                    foreach ($data as $model) {
                        $serializer->addPrimary($userResource, $model, []);
                    }

                    [$primary, $included] = $serializer->serialize();

                    $document = ['data' => $primary];
                    if (count($included)) {
                        $document['included'] = $included;
                    }

                    return json_api_response($document);
                }),
        ]),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-audit', fn () => [
            (new \Flarum\Audit\Extend\Audit())
                ->listen(
                    Event\Banned::class,
                    'discussion_ban.banned',
                    fn (Event\Banned $event) => [
                        'discussion_id' => $event->ban->discussion_id,
                        'user_id' => $event->ban->user_id,
                        'reason' => $event->ban->reason,
                    ]
                )
                ->listen(
                    Event\Unbanned::class,
                    'discussion_ban.unbanned',
                    fn (Event\Unbanned $event) => [
                        'discussion_id' => $event->ban->discussion_id,
                        'user_id' => $event->ban->user_id,
                    ]
                ),
        ]),

    (new Extend\Settings())
        ->default('huseyinfiliz-discussion-ban.showInDiscussionControls', true)
        ->serializeToForum('huseyinfiliz-discussion-ban.showInDiscussionControls', 'huseyinfiliz-discussion-ban.showInDiscussionControls', 'boolval')
        ->default('huseyinfiliz-discussion-ban.showInPostControls', true)
        ->serializeToForum('huseyinfiliz-discussion-ban.showInPostControls', 'huseyinfiliz-discussion-ban.showInPostControls', 'boolval')
        ->default('huseyinfiliz-discussion-ban.hideDiscussionsFromBanned', true),
];