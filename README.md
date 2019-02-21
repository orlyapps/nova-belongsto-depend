# BelongsTo Field with Dependency

[![Latest Version on Packagist](https://img.shields.io/packagist/v/orlyapps/nova-belongsto-depend.svg?style=flat-square)](https://packagist.org/packages/Orlyapps/nova-belongsto-depend)
[![Total Downloads](https://img.shields.io/packagist/dt/orlyapps/nova-belongsto-depend.svg?style=flat-square)](https://packagist.org/packages/Orlyapps/nova-belongsto-depend)

![Sample](https://raw.githubusercontent.com/orlyapps/nova-belongsto-depend/master/docs/sample.gif)

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
                // Reduce the amount of unnecessary data sent
                return $company->departments()->get(['id','name']);
            })
            ->dependsOn('Company'),
        NovaBelongsToDepend::make('Location')
            ->optionsResolve(function ($company) {
                // Reduce the amount of unnecessary data sent
                return $company->locations()->get(['id','name']);
            })
            ->fallback(
                Text::make('Location Name')->rules('required', 'max:255'),
            )
            ->hideLinkToResourceFromDetail()
            ->hideLinkToResourceFromIndex()
            ->nullable()
            ->dependsOn('Company'),

    ];
}
```

## Sample

[Demo Project](https://github.com/orlyapps/laravel-nova-demo)

-   Warehouse hasMany Articles
-   Articles belongsToMany Suppliers
-   Suppliers belongsToMany Articles

1. Select a **Warehouse** and get all articles of the warehouse
2. Select a **Article** and get all suppliers who has this article

```php
public function fields(Request $request)
{
    return [
        ID::make()->sortable(),
        Text::make('Name')->rules('required', 'max:255'),

        NovaBelongsToDepend::make('Warehouse')
        ->options(\App\Warehouse::all())
        ->rules('required'),
        NovaBelongsToDepend::make('Article')
            ->optionsResolve(function ($warehouse) {
                return $warehouse->articles;
            })
            ->dependsOn('Warehouse')
            ->rules('required'),
        NovaBelongsToDepend::make('Supplier')
            ->optionsResolve(function ($article) {
                return \App\Supplier::whereHas('articles', function ($q) use ($article) {
                    $q->where('article_id', $article->id);
                })->get();
            })
            ->dependsOn('Article')
            ->rules('required'),


    ];
}
```

### Security

If you discover any security related issues, please email info@orlyapps.de instead of using the issue tracker.

## Credits

-   [Orlyapps](https://github.com/orlyapps)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
