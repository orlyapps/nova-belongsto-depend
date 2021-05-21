# BelongsTo Field with Dependency

[![Latest Version on Packagist](https://img.shields.io/packagist/v/orlyapps/nova-belongsto-depend.svg?style=flat-square)](https://packagist.org/packages/Orlyapps/nova-belongsto-depend)
[![Total Downloads](https://img.shields.io/packagist/dt/orlyapps/nova-belongsto-depend.svg?style=flat-square)](https://packagist.org/packages/Orlyapps/nova-belongsto-depend)

![Sample](https://raw.githubusercontent.com/orlyapps/nova-belongsto-depend/master/docs/sample.gif)

## Using an older version of Laravel?

This version is compatible with Laravel 5.8 and newer.

If you use an older version of Laravel you can use an older version of the package. These aren't maintained anymore, but they should be pretty stable. We still accept small bugfixes.

-   [v1 for Laravel 5.7+ / PHP 7.0](https://github.com/orlyapps/nova-belongsto-depend/releases/tag/1.0.0)

## Installation

You can install the package in to a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require orlyapps/nova-belongsto-depend
```

Use this field in your Nova Resource

```php

use Orlyapps\NovaBelongsToDepend\NovaBelongsToDepend;

public function fields(Request $request)
{
    return [
        ID::make()->sortable(),
        Text::make('Name')->rules('required', 'max:255'),

        NovaBelongsToDepend::make('Company')
            ->placeholder('Optional Placeholder') // Add this just if you want to customize the placeholder
            ->options(\App\Company::all()),
        NovaBelongsToDepend::make('Department')
            ->placeholder('Optional Placeholder') // Add this just if you want to customize the placeholder
            ->optionsResolve(function ($company) {
                // Reduce the amount of unnecessary data sent
                return $company->departments()->get(['id','name']);
            })
            ->dependsOn('Company'),
        NovaBelongsToDepend::make('Location')
            ->placeholder('Optional Placeholder') // Add this just if you want to customize the placeholder
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

## Options

```placeholder('Optional Placeholder') ```

```openDirection('top') ```
See options values from [vue-multiselect ](https://vue-multiselect.js.org/#sub-props)

## Translation

The following strings are translatable (add then in your language file located in resources/lan/vendor/nova/\*.json).

-   'Oops! No elements found. Consider changing the search query.'
-   'List is empty'
-   'Select'
-   'Press enter to select'
-   'Selected'
-   'Press enter to remove'

If you do use nova-translatable and would like to return the translated name add this to your translatable model: 
```php
    /**
     * @return mixed
     */
    public function getNameAttribute()
    {
        return $this->getTranslations('name')[app()->getLocale()];
    }
```

## Performance Tips

When attaching this field to a resource, you may include the field relation in the `$with` property for that resource to prevent n+1 issues when loading an index page for that resource.
```php
class Company extends Resource
{
  public static $with = [];
}

class Department extends Resource
{
  public static $with = ['company'];
}

class Location extends Resource
{
  public static $with = ['department', 'company'];
}
```

You may also choose to cache your top-level model to reduce the number of queries made to the database for each row in an index.
```php
NovaBelongsToDepend::make('Company')
    ->options(Cache::remember(
        'companies', 
        60, 
        function () { 
            return Company::all(); 
        }
    )),
NovaBelongsToDepend::make('Department')
    ->dependsOn('Company')
    ->optionsResolve(function($company) { 
        return $company->departments;
    })
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

## Depend on several fields

From **version 3** of this package you can depend on several fields.
Just pass them comma separated in the dependsOn method.

```php
->dependsOn('classification', 'brand')
```

Here an example:

-   Classification hasMany Models && belongsToMany Brands
-   Brand hasMany Models && belongsToMany Classification
-   Model belongsTo Classification && belongsTo Brand

1. Select a **Classification** and get all _Brands_ of the classification
2. Select a **Brand** and get all _Models_ that has this _Classification_ **and** _Brand_

```php
public function fields(Request $request)
{
    return [
        ID::make(__('ID'), 'id')->sortable(),
        NovaBelongsToDepend::make('Classification', 'classification')
            ->options(\App\Models\Classification::all()),
        NovaBelongsToDepend::make('Brand', 'brand')
            ->optionsResolve(function ($classification) {
                return $classification->brands()->get(['brands.id', 'brands.name']);
            })
            ->dependsOn('classification'),
        NovaBelongsToDepend::make('Model', 'model', VehicleModel::class)
            ->optionsResolve(function ($depends) {
                return \App\Models\VehicleModel::query()
                    ->where('classification_id', $depends->classification->id)
                    ->where('brand_id', $depends->brand->id)
                    ->get();
            })
            ->dependsOn('classification', 'brand'),
    ];
}
```

### Security

If you discover any security related issues, please email info@orlyapps.de instead of using the issue tracker.

## Credits

-   [Orlyapps](https://github.com/orlyapps)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
