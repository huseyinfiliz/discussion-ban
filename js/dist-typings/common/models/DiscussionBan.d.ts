import Model from 'flarum/common/Model';
import type User from 'flarum/common/models/User';
import type Discussion from 'flarum/common/models/Discussion';
export default class DiscussionBan extends Model {
    reason: () => string | null;
    createdAt: () => Date | null | undefined;
    revokedAt: () => Date | null | undefined;
    user: () => false | User;
    bannedBy: () => false | User;
    revokedBy: () => false | User;
    discussion: () => false | Discussion;
}
