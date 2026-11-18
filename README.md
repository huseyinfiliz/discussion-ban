# Discussion Ban

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/huseyinfiliz/discussion-ban.svg)](https://packagist.org/packages/huseyinfiliz/discussion-ban) [![Total Downloads](https://img.shields.io/packagist/dt/huseyinfiliz/discussion-ban.svg)](https://packagist.org/packages/huseyinfiliz/discussion-ban)

Ban users from individual discussions in [Flarum](https://flarum.org) 2.

## Features

- Ban and unban users from discussion controls, the sidebar, or post menus.
- Search discussion participants when selecting a user.
- Hide the banned user's visible posts and restore them on unban, preserving independent staff moderation.
- Hide discussions from banned users or keep them visible in read-only mode.
- Protect administrators from discussion bans.
- Record ban and unban actions when the optional `flarum/audit` extension is enabled.

## Installation

```sh
composer require huseyinfiliz/discussion-ban
```

## Updating

```sh
composer update huseyinfiliz/discussion-ban
php flarum migrate
php flarum cache:clear
```

**Upgrading from 1.1.2 or earlier:** Posts already hidden before the update remain hidden when an older ban is revoked. Staff can review and restore them manually.

## Permissions

Grant **Ban users** to trusted groups under **Admin > Permissions**.

## Links

- [Packagist](https://packagist.org/packages/huseyinfiliz/discussion-ban)
- [GitHub](https://github.com/huseyinfiliz/discussion-ban)
- [Discuss](https://discuss.flarum.org/d/39825-discussion-ban-ban-users-from-discussions)

## License

MIT
