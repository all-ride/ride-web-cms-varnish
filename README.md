# Ride: Web CMS Varnish

Varnish integration for the CMS of a Ride web application.

This module adds a event listener to node save and delete actions.
When a page is saved or deleted, this module resolves the changed pages and bans those URL's in the Varnish cache.

## Related Modules

- [ride/app](https://github.com/all-ride/ride-app)
- [ride/app-varnish](https://github.com/all-ride/ride-app-varnish)
- [ride/lib-cms](https://github.com/all-ride/ride-lib-cms)
- [ride/lib-event](https://github.com/all-ride/ride-lib-event)
- [ride/lib-varnish](https://github.com/all-ride/ride-lib-varnish)
- [ride/web](https://github.com/all-ride/ride-web)
- [ride/web-cms](https://github.com/all-ride/ride-web-cms)

## Installation

You can use [Composer](http://getcomposer.org) to install this application.

```
composer require ride/web-cms-varnish
```

