import app from 'flarum/common/app';
import DiscussionBan from './models/DiscussionBan';

app.initializers.add('huseyinfiliz-discussion-ban-common', () => {
  app.store.models['discussion-bans'] = DiscussionBan;
});

export { default as DiscussionBan } from './models/DiscussionBan';
