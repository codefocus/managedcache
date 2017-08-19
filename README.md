# ManagedCache

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

This is where your description should go. Try and limit it to a paragraph or two, and maybe throw in a mention of what
PSRs you support to avoid any confusion with users and contributors.

## Install

Via Composer

``` bash
$ composer require codefocus/managedcache
```

Add `ManagedCacheProvider` to the "providers" array in `config/app.php`

``` php
Codefocus\ManagedCache\Providers\ManagedCacheProvider::class
```

## Usage

``` php
ManagedCache::forgetWhen([
        ManagedCache::deleted(User::class),
        ManagedCache::created(User::class),
        ManagedCache::restored(User::class),
        ManagedCache::saved(User::class),
        ManagedCache::relationCreated(User::class, Role::class),
        ManagedCache::relationDeleted(User::class, Role::class),
    ])
    ->put('users.all', $allOfTheUsers, 60);
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email info@codefocus.ca instead of using the issue tracker.

## Credits

- [Menno van Ens][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/codefocus/managedcache.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/codefocus/managedcache/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/codefocus/managedcache.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/codefocus/managedcache.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/codefocus/managedcache.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/codefocus/managedcache
[link-travis]: https://travis-ci.org/codefocus/managedcache
[link-scrutinizer]: https://scrutinizer-ci.com/g/codefocus/managedcache/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/codefocus/managedcache
[link-downloads]: https://packagist.org/packages/codefocus/managedcache
[link-author]: https://github.com/codefocus
[link-contributors]: ../../contributors
