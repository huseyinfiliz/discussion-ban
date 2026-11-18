import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import type PostStreamState from 'flarum/forum/states/PostStreamState';

/** Refresh server-owned moderation state instead of guessing which posts changed. */
export default async function refreshDiscussionPosts(discussion: Discussion, userId: string): Promise<void> {
  const posts = app.store.all<Post>('posts').filter((post) => {
    const user = post.user();
    return post.discussion()?.id() === discussion.id() && user && user.id() === userId;
  });

  await app.store.find<Discussion>('discussions', discussion.id()!);

  for (let offset = 0; offset < posts.length; offset += 50) {
    const batch = posts.slice(offset, offset + 50);
    const refreshed = await app.store.find<Post[]>(
      'posts',
      batch.map((post) => post.id()!)
    );
    const visibleIds = new Set(refreshed.map((post) => post.id()));

    // Core omits hiddenAt for visible posts; the store merges attributes, so
    // explicitly clear an older cached timestamp when the server omits it.
    refreshed.payload.data.forEach((data) => {
      app.store.getById<Post>('posts', data.id)?.pushAttributes({
        hiddenAt: data.attributes?.hiddenAt ?? null,
        isHidden: Boolean(data.attributes?.hiddenAt),
      });
    });

    batch.filter((post) => !visibleIds.has(post.id())).forEach((post) => app.store.remove(post));
  }

  const stream = app.current.get('stream') as PostStreamState | undefined;
  if (stream && stream.discussion.id() === discussion.id()) {
    await stream.goToNumber(stream.number, true);
  }
}
