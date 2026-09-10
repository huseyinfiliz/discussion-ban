import app from 'flarum/forum/app';
import m from 'mithril';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import PostControls from 'flarum/forum/utils/PostControls';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import CommentPost from 'flarum/forum/components/CommentPost';
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

  extend(DiscussionPage.prototype, 'sidebarItems', function (items) {
    if (!app.forum.attribute('huseyinfiliz-discussion-ban.showInDiscussionSidebar')) return;
    if (!this.discussion || !this.discussion.attribute('canBanUsers')) return;

    const bansCount = this.discussion.attribute<number>('discussionBansCount');
    const bannedUserMap = this.discussion.attribute<Record<string, number>>('bannedUserMap') || {};
    const count = bansCount !== undefined ? bansCount : Object.keys(bannedUserMap).length;

    const label =
      count > 0
        ? `${app.translator.trans('huseyinfiliz-discussion-ban.forum.discussion_page.ban_button')} (${count})`
        : app.translator.trans('huseyinfiliz-discussion-ban.forum.discussion_page.ban_button');

    items.add(
      'discussion-ban',
      Button.component(
        {
          className: 'Button Button--icon',
          icon: 'fas fa-ban',
          onclick: () => app.modal.show(BanFromDiscussionModal, { discussion: this.discussion! }),
        },
        label
      ),
      80
    );
  });

  extend(CommentPost.prototype, 'headerItems', function (items) {
    const post = this.attrs.post;
    const user = post.user();
    const userId = user ? String(user.id()) : null;
    const isBanHidden =
      post.attribute('isDiscussionBanHidden') || Boolean(userId && post.discussion()?.attribute<Record<string, number>>('bannedUserMap')?.[userId]);

    if (post.isHidden() && isBanHidden) {
      items.add(
        'discussion-ban-badge',
        m('span.Post-discussionBan-badge', [
          m('i.fas.fa-ban'),
          m('span', app.translator.trans('huseyinfiliz-discussion-ban.forum.post.hidden_by_ban')),
        ]),
        50
      );
    }
  });

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items: any) {
    items.add('discussionBanned', {
      name: 'discussionBanned',
      icon: 'fas fa-ban',
      label: app.translator.trans('huseyinfiliz-discussion-ban.forum.settings.notify_discussion_banned_label'),
    });
    items.add('discussionUnbanned', {
      name: 'discussionUnbanned',
      icon: 'fas fa-user-check',
      label: app.translator.trans('huseyinfiliz-discussion-ban.forum.settings.notify_discussion_unbanned_label'),
    });
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
                  const currentCount = discussion.attribute<number>('discussionBansCount') ?? Object.keys(map).length + 1;
                  const newCount = Math.max(0, currentCount - 1);
                  discussion.pushAttributes({ bannedUserMap: map, discussionBansCount: newCount });

                  app.store.all<any>('posts').forEach((p) => {
                    if (p.discussion()?.id() === discussion.id() && String(p.user()?.id()) === postUserId) {
                      p.pushAttributes({ isHidden: false, hiddenAt: null, isDiscussionBanHidden: false });
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
