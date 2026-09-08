import Extend from 'flarum/common/extenders';
import DiscussionBan from './models/DiscussionBan';

export default [new Extend.Store().add('discussion-bans', DiscussionBan)];
