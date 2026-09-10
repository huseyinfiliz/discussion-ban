import app from 'flarum/forum/app';
import m from 'mithril';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Stream from 'flarum/common/utils/Stream';
import Avatar from 'flarum/common/components/Avatar';
import humanTime from 'flarum/common/helpers/humanTime';
import type Discussion from 'flarum/common/models/Discussion';
import type User from 'flarum/common/models/User';
import type DiscussionBan from '../../common/models/DiscussionBan';

interface Attrs extends IInternalModalAttrs {
  discussion: Discussion;
  preselectedUser?: User;
}

export default class BanFromDiscussionModal extends Modal<Attrs> {
  activeTab: 'ban' | 'list' = 'ban';

  protected searchRequestId = 0;
  protected searchTimeout: number | null = null;

  searchQuery = '';
  isDropdownDismissed = false;
  showResults = true;
  searchResults: User[] = [];
  searching = false;
  selectedUser: User | null = null;
  reason = Stream('');
  loadingBans = false;
  bans: DiscussionBan[] = [];
  bansFilter = Stream('');

  get query(): any {
    const fn = (val?: string) => {
      if (val !== undefined) {
        this.searchQuery = String(val);
        return this.searchQuery;
      }
      return this.searchQuery;
    };
    fn.toString = () => this.searchQuery;
    fn.valueOf = () => this.searchQuery;
    return fn;
  }

  set query(val: any) {
    this.searchQuery = typeof val === 'function' ? String(val()) : String(val ?? '');
  }

  oninit(vnode: any) {
    super.oninit(vnode);

    this.searchRequestId++;
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = null;
    }

    this.isDropdownDismissed = false;
    this.showResults = false;
    this.searchQuery = '';
    this.searchResults = [];
    this.searching = false;

    if (this.attrs.preselectedUser) {
      this.selectedUser = this.attrs.preselectedUser;
    } else {
      this.selectedUser = null;
    }

    this.loadBans();
  }

  className() {
    return 'BanFromDiscussionModal Modal--medium';
  }

  title() {
    return app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.title');
  }

  content() {
    return (
      <div className="Modal-body BanFromDiscussionModal-body">
        <div className="BanFromDiscussionModal-tabs">
          <button
            type="button"
            className={'BanFromDiscussionModal-tab' + (this.activeTab === 'ban' ? ' is-active' : '')}
            onclick={() => (this.activeTab = 'ban')}
          >
            <i className="fas fa-user-plus" />
            <span>{app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.tab_ban')}</span>
          </button>
          <button
            type="button"
            className={'BanFromDiscussionModal-tab' + (this.activeTab === 'list' ? ' is-active' : '')}
            onclick={() => (this.activeTab = 'list')}
          >
            <i className="fas fa-users" />
            <span>
              {app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.tab_list', {
                count: this.bans.length,
              })}
            </span>
          </button>
        </div>

        {this.activeTab === 'ban' ? this.banTab() : this.listTab()}
      </div>
    );
  }

  banTab() {
    const query = this.searchQuery.trim();

    return (
      <div className="BanFromDiscussionModal-content">
        <div className="Form-group">
          <label className="Form-label">{app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.select_user')}</label>
          {this.selectedUser ? (
            <div className="BanFromDiscussionModal-selectedCard">
              <div className="BanFromDiscussionModal-selectedCard-user">
                <Avatar user={this.selectedUser} />
                <div className="BanFromDiscussionModal-selectedCard-info">
                  <strong>{this.selectedUser.displayName()}</strong>
                  <span className="BanFromDiscussionModal-selectedCard-username">@{this.selectedUser.username()}</span>
                </div>
              </div>
              <Button
                className="Button Button--icon Button--link"
                icon="fas fa-times"
                title={app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.change_user')}
                onclick={() => {
                  this.selectedUser = null;
                  this.clearSearch();
                }}
              />
            </div>
          ) : (
            <div className="BanFromDiscussionModal-search">
              <div className="BanFromDiscussionModal-searchInputWrapper">
                <input
                  className="FormControl"
                  type="text"
                  placeholder={String(app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.search_placeholder'))}
                  oninput={(e: any) => this.onQueryInput(e.target.value)}
                  value={this.searchQuery}
                />
                {this.searching && (
                  <div className="BanFromDiscussionModal-searchSpinner">
                    <LoadingIndicator size="small" />
                  </div>
                )}
                {query.length > 0 && !this.searching && (
                  <Button
                    className="Button Button--icon Button--link BanFromDiscussionModal-clearBtn"
                    icon="fas fa-times"
                    onclick={() => this.clearSearch()}
                  />
                )}
              </div>
              {!this.isDropdownDismissed && this.showResults && query.length >= 2 && this.searchResults.length > 0 && (
                <ul className="BanFromDiscussionModal-results">
                  {this.searchResults.map((user) => (
                    <li key={user.id()} onclick={() => this.selectUser(user)}>
                      <Avatar user={user} />
                      <div className="BanFromDiscussionModal-results-name">
                        <strong>{user.displayName()}</strong>
                        <span>@{user.username()}</span>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          )}
        </div>

        <div className="Form-group">
          <label className="Form-label">{app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.reason_label')}</label>
          <textarea
            className="FormControl"
            value={this.reason()}
            oninput={(e: any) => this.reason(e.target.value)}
            rows={3}
            placeholder={String(app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.reason_placeholder'))}
          />
          <div className="helpText">{app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.reason_help')}</div>
        </div>

        {Button.component(
          {
            className: 'Button Button--primary BanFromDiscussionModal-submitBtn',
            disabled: !this.selectedUser || this.loading,
            loading: this.loading,
            onclick: () => this.submit(),
          },
          app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.submit')
        )}
      </div>
    );
  }

  listTab() {
    if (this.loadingBans) {
      return (
        <div className="BanFromDiscussionModal-loading">
          <LoadingIndicator />
        </div>
      );
    }

    const filterText = this.bansFilter().trim().toLowerCase();
    const filteredBans = this.bans.filter((ban) => {
      if (!filterText) return true;
      const u = ban.user();
      if (!u) return false;
      return u.displayName().toLowerCase().includes(filterText) || u.username().toLowerCase().includes(filterText);
    });

    return (
      <div className="BanFromDiscussionModal-content">
        <div className="BanFromDiscussionModal-filterWrapper">
          <i className="fas fa-search BanFromDiscussionModal-filterIcon" />
          <input
            className="FormControl BanFromDiscussionModal-filterInput"
            type="text"
            placeholder={String(app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.search_banned_placeholder'))}
            value={this.bansFilter()}
            oninput={(e: any) => this.bansFilter(e.target.value)}
          />
        </div>

        {filteredBans.length === 0 ? (
          <p className="BanFromDiscussionModal-empty">{app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.no_bans')}</p>
        ) : (
          <ul className="BanFromDiscussionModal-list">
            {filteredBans.map((ban) => {
              const user = ban.user();
              const bannedBy = ban.bannedBy();
              const createdAt = ban.createdAt();

              const bannedByName = bannedBy ? bannedBy.displayName() : String(app.translator.trans('core.lib.username.deleted_text'));

              return (
                <li className="BanFromDiscussionModal-item" key={ban.id()}>
                  <div className="BanFromDiscussionModal-item-avatar">
                    <Avatar user={user || null} />
                  </div>

                  <div className="BanFromDiscussionModal-item-info">
                    <div className="BanFromDiscussionModal-item-header">
                      <span className="BanFromDiscussionModal-item-displayName">
                        {user ? user.displayName() : app.translator.trans('core.lib.username.deleted_text')}
                      </span>
                      {user && <span className="BanFromDiscussionModal-item-username">@{user.username()}</span>}
                    </div>

                    <div className="BanFromDiscussionModal-item-meta">
                      {createdAt && (
                        <span>
                          <i className="far fa-clock" /> {humanTime(createdAt)}
                        </span>
                      )}
                      <span>
                        <i className="fas fa-user-shield" />{' '}
                        {app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.banned_by', {
                          username: bannedByName,
                        })}
                      </span>
                    </div>

                    {ban.reason() && (
                      <div className="BanFromDiscussionModal-item-reason">
                        <i className="fas fa-quote-left" /> {ban.reason()}
                      </div>
                    )}
                  </div>

                  <div className="BanFromDiscussionModal-item-actions">
                    {Button.component({
                      className: 'Button Button--icon Button--danger BanFromDiscussionModal-deleteBtn',
                      icon: 'fas fa-trash-alt',
                      title: app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.unban'),
                      onclick: () => this.revoke(ban),
                    })}
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </div>
    );
  }

  onQueryInput(value: string) {
    this.searchQuery = value;

    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = null;
    }

    const trimmed = value.trim();

    if (trimmed.length < 2) {
      this.searchRequestId++; // Invalidate any running requests
      this.searchResults = [];
      this.searching = false;
      this.isDropdownDismissed = false;
      this.showResults = false;
      m.redraw();
      return;
    }

    const requestId = ++this.searchRequestId;
    this.searching = true;
    this.showResults = true;
    this.isDropdownDismissed = false;
    m.redraw();

    this.searchTimeout = window.setTimeout(() => {
      this.performSearch(trimmed, requestId);
    }, 300);
  }

  getDiscussionParticipantUsers(): User[] {
    const discussionId = this.attrs.discussion.id();
    const bannedUserMap = this.attrs.discussion.attribute<Record<string, number>>('bannedUserMap') || {};
    const currentUserId = String(app.session.user?.id());
    const seen = new Set<string>();
    const participants: User[] = [];

    app.store.all<any>('posts').forEach((post) => {
      if (post.discussion()?.id() !== discussionId) return;
      const u = post.user();
      if (!u || !u.id()) return;
      const id = String(u.id());
      if (id === currentUserId || u.isAdmin() || bannedUserMap[id] || seen.has(id)) return;
      seen.add(id);
      participants.push(u);
    });

    return participants;
  }

  performSearch(query: string, requestId?: number) {
    const currentRequestId = requestId ?? ++this.searchRequestId;

    app
      .request<any>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/users/discussion-participants/${this.attrs.discussion.id()}`,
        params: { filter: { q: query } },
      })
      .then((response: any) => {
        // Ignore response if a newer search was initiated
        if (currentRequestId !== this.searchRequestId) {
          return;
        }

        const pushed = response ? app.store.pushPayload(response) : [];
        const apiUsers = Array.isArray(pushed) ? pushed : pushed ? [pushed] : [];

        // Also merge any matching participants already loaded in the store
        const q = query.toLowerCase();
        const localUsers = this.getDiscussionParticipantUsers().filter((u) => {
          const username = u.username()?.toLowerCase() || '';
          const displayName = u.displayName()?.toLowerCase() || '';
          return username.includes(q) || displayName.includes(q);
        });

        // Combine and deduplicate
        const userMap = new Map<string, User>();
        [...apiUsers, ...localUsers].forEach((user) => {
          if (user && user.id()) {
            userMap.set(String(user.id()), user);
          }
        });

        const bannedUserMap = this.attrs.discussion.attribute<Record<string, number>>('bannedUserMap') || {};
        const currentUserId = String(app.session.user?.id());

        this.searchResults = Array.from(userMap.values()).filter((user) => {
          const uid = String(user.id());
          return uid !== currentUserId && !user.isAdmin() && !bannedUserMap[uid];
        });
        this.searching = false;
        this.showResults = true;
        this.isDropdownDismissed = false;

        m.redraw();
      })
      .catch(() => {
        if (currentRequestId !== this.searchRequestId) {
          return;
        }

        // Fallback to local participants from loaded discussion posts
        const q = query.toLowerCase();
        this.searchResults = this.getDiscussionParticipantUsers().filter((u) => {
          const username = u.username()?.toLowerCase() || '';
          const displayName = u.displayName()?.toLowerCase() || '';
          return username.includes(q) || displayName.includes(q);
        });
        this.searching = false;
        this.showResults = true;
        this.isDropdownDismissed = false;

        m.redraw();
      })
      .finally(() => {
        if (currentRequestId === this.searchRequestId && this.searching) {
          this.searching = false;
          m.redraw();
        }
      });
  }

  search(query?: string) {
    const trimmed = (query !== undefined ? query : this.searchQuery).trim();
    if (trimmed.length < 2) {
      this.searchRequestId++;
      this.searchResults = [];
      this.searching = false;
      m.redraw();
      return;
    }
    const requestId = ++this.searchRequestId;
    this.searching = true;
    this.showResults = true;
    this.isDropdownDismissed = false;
    m.redraw();
    this.performSearch(trimmed, requestId);
  }

  clearSearch() {
    this.searchRequestId++;
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = null;
    }

    this.searchQuery = '';
    this.searchResults = [];
    this.searching = false;
    this.isDropdownDismissed = false;
    this.showResults = false;

    m.redraw();
  }

  selectUser(user: User) {
    this.selectedUser = user;
    this.clearSearch();
  }

  submit() {
    if (!this.selectedUser) return;

    this.loading = true;

    const trimmedReason = this.reason().trim();

    app.store
      .createRecord<DiscussionBan>('discussion-bans')
      .save({
        reason: trimmedReason.length > 0 ? trimmedReason : null,
        relationships: {
          discussion: this.attrs.discussion,
          user: this.selectedUser,
        },
      })
      .then((ban: DiscussionBan) => {
        this.loading = false;

        const userId = String(this.selectedUser!.id());
        const map = { ...(this.attrs.discussion.attribute<Record<string, number>>('bannedUserMap') || {}) };
        map[userId] = Number(ban.id());
        const currentCount = this.attrs.discussion.attribute<number>('discussionBansCount') ?? Object.keys(map).length - 1;
        const newCount = currentCount + 1;
        this.attrs.discussion.pushAttributes({ bannedUserMap: map, discussionBansCount: newCount });

        app.store.all<any>('posts').forEach((post) => {
          if (post.discussion()?.id() === this.attrs.discussion.id() && String(post.user()?.id()) === userId) {
            post.pushAttributes({ isHidden: true, hiddenAt: new Date().toISOString(), isDiscussionBanHidden: true });
          }
        });

        app.alerts.show({ type: 'success' }, app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.ban_success'));
        this.hide();
        m.redraw();
      })
      .catch((e: any) => {
        this.loading = false;
        m.redraw();
        throw e;
      });
  }

  loadBans() {
    this.loadingBans = true;
    m.redraw();

    app.store
      .find<DiscussionBan[]>('discussion-bans', {
        filter: { discussion: this.attrs.discussion.id() },
        include: 'user,bannedBy',
      })
      .then((bans: DiscussionBan[]) => {
        this.bans = bans;
        this.loadingBans = false;
        m.redraw();
      })
      .catch(() => {
        this.loadingBans = false;
        m.redraw();
      });
  }

  revoke(ban: DiscussionBan) {
    if (!confirm(String(app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.confirm_unban')))) {
      return;
    }

    const user = ban.user();
    const userId = user ? String(user.id()) : null;

    ban.delete().then(() => {
      this.bans = this.bans.filter((b) => b.id() !== ban.id());

      if (userId) {
        const map = { ...(this.attrs.discussion.attribute<Record<string, number>>('bannedUserMap') || {}) };
        delete map[userId];
        const currentCount = this.attrs.discussion.attribute<number>('discussionBansCount') ?? Object.keys(map).length + 1;
        const newCount = Math.max(0, currentCount - 1);
        this.attrs.discussion.pushAttributes({ bannedUserMap: map, discussionBansCount: newCount });

        app.store.all<any>('posts').forEach((post) => {
          if (post.discussion()?.id() === this.attrs.discussion.id() && String(post.user()?.id()) === userId) {
            post.pushAttributes({ isHidden: false, hiddenAt: null, isDiscussionBanHidden: false });
          }
        });
      }

      app.alerts.show({ type: 'success' }, app.translator.trans('huseyinfiliz-discussion-ban.forum.modal.unban_success'));
      m.redraw();
    });
  }
}
