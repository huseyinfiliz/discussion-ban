# Discussion Ban

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/huseyinfiliz/discussion-ban.svg)](https://packagist.org/packages/huseyinfiliz/discussion-ban) [![Total Downloads](https://img.shields.io/packagist/dt/huseyinfiliz/discussion-ban.svg)](https://packagist.org/packages/huseyinfiliz/discussion-ban)

A [Flarum](https://flarum.org) extension. Allows administrators and authorized moderators to ban users from specific discussions, hiding their existing posts and preventing them from posting or interacting further in those discussions.

## Features

- **Discussion-Level Bans:** Ban disruptive users from specific discussions directly from discussion controls or post action menus.
- **Automatic Post Moderation:** Automatically hides all existing posts by the banned user in that discussion, and restores them if the ban is revoked.
- **Reply Restriction:** Banned users are strictly prevented from posting further replies.
- **Discussion Visibility Control:** Optional setting to hide the discussion completely from banned users or keep it visible in read-only mode.
- **Participant Search:** Quickly find and ban active participants within the discussion modal.
- **Admin Shield:** Protects forum administrators from being banned.
- **Audit Log Integration:** Seamlessly logs ban and unban events when `flarum/audit` is installed.

## Installation

Install with composer:

```sh
composer require huseyinfiliz/discussion-ban:"*"
```

## Updating

```sh
composer update huseyinfiliz/discussion-ban:"*"
php flarum migrate
php flarum cache:clear
```

## Permissions

Navigate to the **Admin > Permissions** page to grant the **Ban users** from discussions permission to trusted moderator groups.

## Links

- [Packagist](https://packagist.org/packages/huseyinfiliz/discussion-ban)
- [GitHub](https://github.com/huseyinfiliz/discussion-ban)
- [Discuss](https://discuss.flarum.org/d/39825-discussion-ban-ban-users-from-discussions)

## License

MIT
