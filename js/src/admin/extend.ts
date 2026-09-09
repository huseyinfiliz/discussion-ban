import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-ban',
        label: app.translator.trans('huseyinfiliz-discussion-ban.admin.permissions.ban_users_label'),
        permission: 'discussion.banUsers',
      }),
      'moderate',
      20
    )
    .setting(() => ({
      setting: 'huseyinfiliz-discussion-ban.showInDiscussionControls',
      type: 'boolean',
      label: app.translator.trans('huseyinfiliz-discussion-ban.admin.settings.show_in_discussion_controls'),
    }))
    .setting(() => ({
      setting: 'huseyinfiliz-discussion-ban.showInDiscussionSidebar',
      type: 'boolean',
      label: app.translator.trans('huseyinfiliz-discussion-ban.admin.settings.show_in_discussion_sidebar'),
    }))
    .setting(() => ({
      setting: 'huseyinfiliz-discussion-ban.showInPostControls',
      type: 'boolean',
      label: app.translator.trans('huseyinfiliz-discussion-ban.admin.settings.show_in_post_controls'),
    }))
    .setting(() => ({
      setting: 'huseyinfiliz-discussion-ban.hideDiscussionsFromBanned',
      type: 'boolean',
      label: app.translator.trans('huseyinfiliz-discussion-ban.admin.settings.hide_discussions_from_banned'),
    }))
    .setting(() => ({
      setting: 'huseyinfiliz-discussion-ban.sendNotifications',
      type: 'boolean',
      label: app.translator.trans('huseyinfiliz-discussion-ban.admin.settings.send_notifications'),
    })),
];
