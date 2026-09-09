import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import type Discussion from 'flarum/common/models/Discussion';

export default class DiscussionBannedNotification extends Notification {
  icon() {
    return 'fas fa-ban';
  }

  href() {
    const discussion = this.attrs.notification.subject() as Discussion | undefined;
    return discussion ? app.route.discussion(discussion) : '#';
  }

  content() {
    const notification = this.attrs.notification;
    const user = notification.fromUser();
    const discussion = notification.subject() as Discussion | undefined;

    return app.translator.trans('huseyinfiliz-discussion-ban.forum.notifications.discussion_banned_text', {
      username: user ? user.displayName() : app.translator.trans('core.lib.username.deleted_text'),
      title: discussion ? discussion.title() : '',
    });
  }

  excerpt() {
    const data = this.attrs.notification.data() as { reason?: string | null } | undefined;
    return data?.reason || '';
  }
}
