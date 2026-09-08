<?php

namespace HuseyinFiliz\DiscussionBan\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Post\Post;
use Flarum\User\User;
use HuseyinFiliz\DiscussionBan\DiscussionBan;
use HuseyinFiliz\DiscussionBan\Event;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Tobyz\JsonApiServer\Context as JsonApiContext;
use Tobyz\JsonApiServer\Pagination\Pagination;

/** @extends AbstractDatabaseResource<DiscussionBan> */
class DiscussionBanResource extends AbstractDatabaseResource
{
    public function __construct(
        protected Dispatcher $events
    ) {}

    public function type(): string
    {
        return 'discussion-bans';
    }

    public function model(): string
    {
        return DiscussionBan::class;
    }

    public function scope(Builder $query, JsonApiContext $context): void
    {
        $query->whereNull('revoked_at');
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['user', 'bannedBy'])
                ->query(function ($query, ?Pagination $pagination, Context $context, array $filters, ?array $sort, int $offset, ?int $limit): Context {
                    $actor = $context->getActor();
                    $discussionId = $filters['discussion'] ?? null;

                    if ($discussionId) {
                        $discussion = Discussion::findOrFail($discussionId);
                        $actor->assertCan('banUsers', $discussion);

                        $query->where('discussion_id', $discussionId);
                    } else {
                        $actor->assertAdmin();
                    }

                    if (! empty($filters['q'])) {
                        $q = str_replace(['%', '_'], ['\%', '\_'], $filters['q']);
                        $query->whereHas('user', function (Builder $uQuery) use ($q) {
                            $uQuery->where('username', 'like', "%{$q}%");
                        });
                    }

                    $query->with(['user', 'bannedBy'])->latest('id');

                    if ($pagination && method_exists($pagination, 'apply')) {
                        $pagination->apply($query);
                    }

                    return $context->withQuery($query);
                })
                ->paginate(50),

            Endpoint\Create::make()
                ->authenticated(),

            Endpoint\Delete::make()
                ->authenticated(),
        ];
    }

    public function creating(object $model, JsonApiContext $context): ?object
    {
        /** @var DiscussionBan $model */
        $actor = $context->getActor();

        $discussionId = $model->discussion_id
            ?? Arr::get($context->body(), 'data.relationships.discussion.data.id')
            ?? Arr::get($context->body(), 'data.attributes.discussion_id');

        $userId = $model->user_id
            ?? Arr::get($context->body(), 'data.relationships.user.data.id')
            ?? Arr::get($context->body(), 'data.attributes.user_id');

        if (! $discussionId || ! $userId) {
            throw new ValidationException(['user' => 'Discussion and user are required.']);
        }

        $discussion = Discussion::findOrFail($discussionId);
        $actor->assertCan('banUsers', $discussion);

        $targetUser = User::findOrFail($userId);

        if ((int) $targetUser->id === (int) $actor->id) {
            throw new ValidationException(['user' => 'Cannot ban yourself.']);
        }

        if ($targetUser->isAdmin()) {
            throw new ValidationException(['user' => 'Cannot ban an administrator.']);
        }

        if (DiscussionBan::activeBan((int) $discussionId, (int) $targetUser->id)) {
            throw new ValidationException(['user' => 'This user is already banned from this discussion.']);
        }

        $rawReason = $model->reason ?? Arr::get($context->body(), 'data.attributes.reason');
        $model->reason = ! empty(trim((string) $rawReason)) ? trim((string) $rawReason) : null;

        $model->discussion_id = (int) $discussionId;
        $model->user_id = (int) $targetUser->id;
        $model->banned_by_id = $actor->id;
        $model->created_at = Carbon::now();

        return parent::creating($model, $context);
    }

    public function created(object $model, JsonApiContext $context): ?object
    {
        /** @var DiscussionBan $model */
        $actor = $context->getActor();

        Post::where('discussion_id', $model->discussion_id)
            ->where('user_id', $model->user_id)
            ->whereNull('hidden_at')
            ->get()
            ->each(function (Post $post) use ($actor) {
                $post->hide($actor);
                $post->save();
                foreach ($post->releaseEvents() as $event) {
                    $this->events->dispatch($event);
                }
            });

        DiscussionBan::resetCache();

        $this->events->dispatch(new Event\Banned($model, $actor));

        return parent::created($model, $context);
    }

    public function delete(object $model, JsonApiContext $context): void
    {
        /** @var DiscussionBan $model */
        $actor = $context->getActor();

        $discussion = $model->discussion ?: Discussion::findOrFail($model->discussion_id);
        $actor->assertCan('banUsers', $discussion);

        $model->revoked_at = Carbon::now();
        $model->revoked_by_id = $actor->id;
        $model->save();

        DiscussionBan::resetCache();

        Post::where('discussion_id', $model->discussion_id)
            ->where('user_id', $model->user_id)
            ->whereNotNull('hidden_at')
            ->get()
            ->each(function (Post $post) {
                $post->restore();
                $post->save();
                foreach ($post->releaseEvents() as $event) {
                    $this->events->dispatch($event);
                }
            });

        $this->events->dispatch(new Event\Unbanned($model, $actor));
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('reason')
                ->writable()
                ->nullable(),

            Schema\DateTime::make('createdAt')
                ->property('created_at'),

            Schema\DateTime::make('revokedAt')
                ->property('revoked_at'),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->writableOnCreate()
                ->required()
                ->includable(),

            Schema\Relationship\ToOne::make('bannedBy')
                ->type('users')
                ->includable(),

            Schema\Relationship\ToOne::make('revokedBy')
                ->type('users')
                ->includable(),

            Schema\Relationship\ToOne::make('discussion')
                ->type('discussions')
                ->writableOnCreate()
                ->required()
                ->includable(),
        ];
    }
}