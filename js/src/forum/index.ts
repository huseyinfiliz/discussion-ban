import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import PostControls from 'flarum/forum/utils/PostControls';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import BanFromDiscussionModal from './components/BanFromDiscussionModal';

export { default as extend } from './extend';

app.initializers.add('huseyinfiliz-discussion-ban', () => {
  extend(DiscussionControls, 'moderationControls', function (items, discussion: Discussion) {
    if (!app.forum.attribute('huseyinfiliz-discussion-ban.showInDiscussionControls')) return;
    if (!discussion.attribute('canBanUsers')) return;

    items.add(
      'ban-from-discussion',
      Button.component(
        {
          icon: 'fas fa-ban',
          onclick: () => app.modal.show(BanFromDiscussionModal, { discussion }),
        },
        app.translator.trans('huseyinfiliz-discussion-ban.forum.discussion_controls.ban_from_discussion')
      )
    );
  });

  extend(PostControls, 'moderationControls', function (items, post: Post) {
    if (!app.forum.attribute('huseyinfiliz-discussion-ban.showInPostControls')) return;

    const discussion = post.discussion();
    if (!discussion || !discussion.attribute('canBanUsers')) return;

    const postUser = post.user();
    if (!postUser || postUser.id() === app.session.user?.id() || postUser.isAdmin()) return;

    const postUserId = String(postUser.id());
    const bannedUserMap = discussion.attribute<Record<string, number>>('bannedUserMap') || {};
    const banId = bannedUserMap[postUserId];
    const isBanned = Boolean(banId);

    if (isBanned) {
      items.add(
        'discussion-ban',
        Button.component(
          {
            icon: 'fas fa-user-check',
            onclick: () => {
              if (!confirm(String(app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.confirm_unban')))) {
                return;
              }

              app
                .request({
                  method: 'DELETE',
                  url: `${app.forum.attribute('apiUrl')}/discussion-bans/${banId}`,
                })
                .then(() => {
                  const map = { ...(discussion.attribute<Record<string, number>>('bannedUserMap') || {}) };
                  delete map[postUserId];
                  discussion.pushAttributes({ bannedUserMap: map });

                  app.store.all<any>('posts').forEach((p) => {
                    if (p.discussion()?.id() === discussion.id() && String(p.user()?.id()) === postUserId) {
                      p.pushAttributes({ isHidden: false, hiddenAt: null });
                    }
                  });

                  app.alerts.show({ type: 'success' }, app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.unban_success'));
                  m.redraw();
                });
            },
          },
          app.translator.trans('huseyinfiliz-discussion-ban.forum.post_controls.unban_from_discussion')
        )
      );
    } else {
      items.add(
        'discussion-ban',
        Button.component(
          {
            icon: 'fas fa-ban',
            onclick: () => app.modal.show(BanFromDiscussionModal, { discussion, preselectedUser: postUser }),
          },
          app.translator.trans('huseyinfiliz-discussion-ban.forum.post_controls.ban_from_discussion')
        )
      );
    }
  });
});
