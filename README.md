[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Code coverage][ico-codecov]][link-codecov]
[![Quality Score][ico-code-quality]][link-code-quality]
[![StyleCI][ico-styleci]][link-styleci]
[![Total Downloads][ico-downloads]][link-downloads]

# ManagedCache

> "There are only two hard problems in Computer Science: cache invalidation and naming things."

--  Phil Karlton

ManagedCache solves one of these.

When caching data (say, a fully hydrated `User` with all of its related data -- roles, subscriptions, preferences, etc.), you would normally have to invalidate that cache in _every part of the code where one of those roles, subscriptions or preferences is modified_.

ManagedCache lets you define the invalidation criteria in the same statement that caches the data.

## Requirements

### Cache driver requirements

ManagedCache uses [tags](https://laravel.com/docs/master/cache#cache-tags) to perform its automatic event-based invalidation. This means that your cache driver should support tags (the `file` and `database` drivers do not).

ManagedCache currently only supports the Memcached cache driver.
Support for other cache drivers such as Redis is planned.

### Model requirements

Models that are used in [invalidation conditions](#automatic-invalidation) should have an integer primary key.

## Install

Via Composer

``` bash
$ composer require codefocus/managedcache
```

## Quick start

### Provider

Add `ManagedCacheProvider` to the "providers" array in `config/app.php`

``` php
Codefocus\ManagedCache\Providers\ManagedCacheProvider::class
```

### Facade

ManagedCache also provides a Facade.

To use it, add `Codefocus\ManagedCache\Facades\ManagedCache` to your `use` statements.

## Usage

### Compatibility

ManagedCache implements the `Illuminate\Contracts\Cache\Store` interface, and can be used as a drop-in replacement for the `Cache` Facade.

``` php
//  Retrieve a user.
$user = User::with(['roles', 'subscriptions', 'preferences'])->find($userId);

//  Store data as you normally would.
$cacheKey = 'users(' . $userId . ')';
ManagedCache::put($cacheKey, $user, 120);

...

//  Retrieve data as you normally would.
$user = ManagedCache::get($cacheKey);
```

### Automatic invalidation

To automatically invalidate this data, call the `setForgetConditions()` function at the start of the function chain, passing in an array of invalidation conditions.

These invalidation conditions (`Codefocus\ManagedCache\Condition` objects) are named after the Eloquent events that trigger them, and can be created with intuitive helper functions:

``` php
//  Store data, and invalidate this cache when one of these conditions is met:
//  - This User is deleted
//  - This User is updated
//  - A Role is attached to this User
//  - A Role is detached from this User
//  - A Role attached to this User is updated
//  - A Subscription is attached to this User
//  - A Subscription is detached from this User
//  - A Subscription attached to this User is updated
//  - A Preference is attached to this User
//  - A Preference is detached from this User
//  - A Preference attached to this User is updated
ManagedCache
    ::setForgetConditions(function ($conditionBuilder) use ($user) {
        return $conditionBuilder
            ->modelDeleted($user)
            ->modelUpdated($user)
            ->relatedModelAttached($user, Role::class)
            ->relatedModelDetached($user, Role::class)
            ->relatedModelUpdated($user, Role::class)
            ->relatedModelAttached($user, Subscription::class)
            ->relatedModelDetached($user, Subscription::class)
            ->relatedModelUpdated($user, Subscription::class)
            ->relatedModelAttached($user, Preference::class)
            ->relatedModelDetached($user, Preference::class)
            ->relatedModelUpdated($user, Preference::class);
    })
    ->put($cacheKey, $user, 120);
```

As you can see, more complex data with many invalidation conditions could get cumbersome to define. To invalidate a cache key on _any_ of the Eloquent events (`created`, `updated`, `saved`, `deleted` or `restored`) for the specified Model class or instance, use `anyModelEvent()` and `anyRelatedModelEvent()`:

``` php
//  Store data, and invalidate this cache when one of these conditions is met:
//  - This User is created, updated, saved, deleted or restored
//  - A Role attached to this User is created, updated, saved, deleted or restored
//  - A Subscription attached to this User is created, updated, saved, deleted or restored
//  - A Preference attached to this User is created, updated, saved, deleted or restored
ManagedCache
    ::setForgetConditions(function ($conditionBuilder) use ($user) {
        return $conditionBuilder
            ->anyModelEvent($user)
            ->anyRelatedModelEvent($user, Role::class)
            ->anyRelatedModelEvent($user, Subscription::class)
            ->anyRelatedModelEvent($user, Preference::class);
    })
    ->put($cacheKey, $user, 120);
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email info@codefocus.ca instead of using the issue tracker.

## Credits

- [Menno van Ens][link-author]
- [Anthony Tsui](https://github.com/matresstester)
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/codefocus/managedcache.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/codefocus/managedcache/master.svg?style=flat-square
[ico-codecov]: https://img.shields.io/codecov/c/github/codefocus/managedcache.svg
[ico-code-quality]: https://img.shields.io/scrutinizer/g/codefocus/managedcache.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/100775975/shield
[ico-downloads]: https://img.shields.io/packagist/dt/codefocus/managedcache.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/codefocus/managedcache
[link-travis]: https://travis-ci.org/codefocus/managedcache
[link-codecov]: https://codecov.io/gh/codefocus/managedcache
[link-code-quality]: https://scrutinizer-ci.com/g/codefocus/managedcache
[link-styleci]: https://styleci.io/repos/100775975
[link-downloads]: https://packagist.org/packages/codefocus/managedcache
[link-author]: https://github.com/codefocus
[link-contributors]: ../../contributors
