import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Discussion from 'flarum/common/models/Discussion';
import type User from 'flarum/common/models/User';
import type DiscussionBan from '../../common/models/DiscussionBan';
interface Attrs extends IInternalModalAttrs {
    discussion: Discussion;
    preselectedUser?: User;
}
export default class BanFromDiscussionModal extends Modal<Attrs> {
    activeTab: 'ban' | 'list';
    searchQuery: string;
    isDropdownDismissed: boolean;
    showResults: boolean;
    searchResults: User[];
    searching: boolean;
    selectedUser: User | null;
    reason: any;
    loadingBans: boolean;
    bans: DiscussionBan[];
    bansFilter: any;
    searchTimeout: number | null;
    get query(): any;
    set query(val: any);
    oninit(vnode: any): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    banTab(): JSX.Element;
    listTab(): JSX.Element;
    onQueryInput(value: string): void;
    search(query?: string): void;
    clearSearch(): void;
    selectUser(user: User): void;
    submit(): void;
    loadBans(): void;
    revoke(ban: DiscussionBan): void;
}
export {};
