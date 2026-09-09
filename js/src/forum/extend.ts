import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import DiscussionBannedNotification from './components/DiscussionBannedNotification';
import DiscussionUnbannedNotification from './components/DiscussionUnbannedNotification';

export default [
  ...commonExtend,

  new Extend.Notification()
    .add('discussionBanned', DiscussionBannedNotification)
    .add('discussionUnbanned', DiscussionUnbannedNotification),
];
