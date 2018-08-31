# BelongsTo Field with Dependency

[![Latest Version on Packagist](https://img.shields.io/packagist/v/orlyapps/nova-belongsto-depend.svg?style=flat-square)](https://packagist.org/packages/Orlyapps/nova-belongsto-depend)
[![Total Downloads](https://img.shields.io/packagist/dt/orlyapps/nova-belongsto-depend.svg?style=flat-square)](https://packagist.org/packages/Orlyapps/nova-belongsto-depend)

## Installation

You can install the package in to a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require orlyapps/nova-belongsto-depend
```

Use this field in your Nova Resource

```php
public function fields(Request $request)
{
    return [
        ID::make()->sortable(),
        Text::make('Name')->rules('required', 'max:255'),

        NovaBelongsToDepend::make('Company')
            ->options(\App\Company::all()),
        NovaBelongsToDepend::make('Department')
            ->optionsResolve(function ($company) {
                return $company->departments;
            })
            ->dependsOn('Company'),
        NovaBelongsToDepend::make('Location')
            ->optionsResolve(function ($company) {
                return $company->locations;
            })
            ->dependsOn('Company'),

    ];
}
```

## Usage

TODO

### Security

If you discover any security related issues, please email info@orlyapps.de instead of using the issue tracker.

## Credits

-   [Orlyapps](https://github.com/orlyapps)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
