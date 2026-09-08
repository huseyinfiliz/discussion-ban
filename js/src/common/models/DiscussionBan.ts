import Model from 'flarum/common/Model';
import type User from 'flarum/common/models/User';
import type Discussion from 'flarum/common/models/Discussion';

export default class DiscussionBan extends Model {
  reason = Model.attribute<string | null>('reason');
  createdAt = Model.attribute('createdAt', Model.transformDate);
  revokedAt = Model.attribute('revokedAt', Model.transformDate);

  user = Model.hasOne<User>('user');
  bannedBy = Model.hasOne<User>('bannedBy');
  revokedBy = Model.hasOne<User>('revokedBy');
  discussion = Model.hasOne<Discussion>('discussion');
}
