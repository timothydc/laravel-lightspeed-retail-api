# laravel-lightspeed-retail-api

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

## Installation

Via Composer

``` bash
composer require timothydc/laravel-lightspeed-retail-api
```

## Usage

For general information on how to use the Lightspeed Retail API, refer to the official [documentation][ls-docs].


### Register API client

Before creating an API connection, you will need to sign up for an API client with Lightspeed Retail.
You can do this via [the client portal][ls-client-portal-register]. The process can take up to 48 hours.
_Bummer, I know._

The API client is developer friendly, you can set `http://localhost:8080` as your redirect URI.
Remember the value of your redirect URI, we will need it later on.


### Configure .env

After your API client is approved you will receive a `key` and `secret`. Add those values to your `.env` file.

```bash
LIGHTSPEED_RETAIL_API_KEY=xxx
LIGHTSPEED_RETAIL_API_SECRET=xxx
```

### Publish resources

You can publish all resources, or you may choose to publish them separately:

```bash
php artisan vendor:publish --tag="lightspeed-api"

php artisan vendor:publish --tag="lightspeed-api:config"
php artisan vendor:publish --tag="lightspeed-api:migrations"
```

The API tokens are stored in the database, by default. So run your migrations.

```bash
php artisan migrate
```


### Request initial access token

Before we can request an access token you need to connect your Retail POS to this app. 

You can manage the access level to your POS data via a scope. Choose a `$scope` from the options in `TimothyDC\LightspeedRetailApi\Scope`.
More information on the scopes can be found in the [documentation][ls-docs-scopes].

#### Via command line

```bash
$ php artisan retail:auth
```

The command will ask you about the scope, and you will get an URL in return. Excellent deal!

#### Via controller
```php
use TimothyDC\LightspeedRetailApi\Scope;
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;

$scope = Scope::EMPLOYEE_ALL;

return redirect()->to(LightspeedRetailApi::redirectToAuthorizationPortal($scope));
```

After requesting your initial access token you will be redirected to the `Redirect URI` 
you provided when configuring your client information via the [Lightspeed Retail API Client][ls-client-portal].


### Configure routing
Register the following route in your `routes/web.php`.
Update `your-redirect-uri` with the redirect URI you entered in the API client.

```php
use \TimothyDC\LightspeedRetailApi\Http\Controllers\SaveAccessTokenController;

Route::get('your-redirect-uri', [SaveAccessTokenController::class, '__invoke']);
```


### Configure controller
The `SaveAccessTokenController` will store the initial access token and make follow-up request for the refresh token.
Afterwards you will be redirected to your `home` route.
If you would like to alter the redirect you may extend this controller.


### Make API calls

You can now access the API. All resources return a [Laravel collection][laravel-docs-collections]... which means lots of fun!

```php
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;

// get all
$account = LightspeedRetailApi::api()->account()->get();

// filter with GET (with a limit and custom sorting
$categories = LightspeedRetailApi::api()->category()->get(null, ['limit' => 10, 'orderby' => 'name']);

// get category with ID 20
$categories = LightspeedRetailApi::api()->category()->get(20);

// same as above, but better
$categories = LightspeedRetailApi::api()->category()->first(20);

// some resources support custom arguments
$label = LightspeedRetailApi::api()->itemAsLabel()->getLabelById('1', 'ItemLabel', true);
```

Note that not all [resources][ls-added-resources] are added (yet). Feel free to add them yourself via a pull request!
If you would like to filter the `GET`-results you can [look at the query parameters API][ls-query-parameters]

```php

// advanced filtering

// get categories with an ID > 10
$categories = LightspeedRetailApi::api()->category()->get(null, ['categoryID' => ['operator' => '>', 'value' => 10]]);

// get categories with their parent relation
$categories = LightspeedRetailApi::api()->category()->get(null, ['load_relations' => ['Parent']]);

```

---

### Automatic model synchronisation [optional]
If you would like to automatically synchronise your data to Lightspeed,
you can add the `HasLightspeedRetailResources` trait and the `AutomaticSynchronisationInterface` interface to your model

In `getLightspeedRetailResourceMapping()` you want to map your model fields to the Lightspeed resource.
The order of the resources is the order of the synchronisation.
In the example below we put the manufacturer resource before the product resource
because we need the `manufacturer id` for when we are syncing the product.

In `getLightspeedRetailResourceName()` you need to define the Lightspeed resource that represents your model. For example:
```php
public function getLightspeedRetailResourceName(): string
{
    return \TimothyDC\LightspeedRetailApi\Services\Lightspeed\ResourceItem::$resource;
}
```


Don't forget to add the `HasLightspeedRetailResources` trait to your `manufacturer` resource too.
```php
use TimothyDC\LightspeedRetailApi\Traits\HasLightspeedRetailResources;
use TimothyDC\LightspeedRetailApi\Services\Lightspeed\{ResourceItem, ResourceVendor};

class Product extends \Illuminate\Database\Eloquent\Model
{
    use HasLightspeedRetailResources;

    public static function getLightspeedRetailResourceMapping(): array
    {
        return [
            ResourceVendor::$resource => [
                ResourceVendor::$name => 'product_vendor'
            ],
            ResourceItem::$resource => [
                ResourceItem::$description => 'name',
                ResourceItem::$manufacturerId => ['manufacturer_id', 'manufacturer.id'],
                ResourceItem::$archived => ['active', 'archive'],
            ],
        ];
    }
}
```

You will notice some resources in the mapping have an array value.
The first item in the array references the value which will be checked for a change,
The second item is the value that will be sent to Lightspeed. It also accepts [mutators][ls-docs-mutators]:

In case of a relationship, the first value is the local foreign key.
The second, is the related primary key.

    
```php
public function getArchivedAttribute(): bool
{
    return $this->attributes['active'] === false;
}
```

By default, the synchronisation process listens to your model events `created`, `updated` and `deleted`.
Update the array if you want to listen to other events.

```php
use TimothyDC\LightspeedRetailApi\Traits\HasLightspeedRetailResources;

class Product extends \Illuminate\Database\Eloquent\Model
{
    use HasLightspeedRetailResources;

    public static function getLightspeedRetailApiTriggerEvents(): array
    {
        return ['created', 'updated', 'deleted'];
    }
}

```

If you would like to send fields to Lightspeed, even when the value isn't changed. You can add them to the `$lsForceSyncFields` array.

```php
use TimothyDC\LightspeedRetailApi\Traits\HasLightspeedRetailResources;

class Product extends \Illuminate\Database\Eloquent\Model
{
    use HasLightspeedRetailResources;

    public array $lsForceSyncFields = ['ean'];
}
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email mail@timothydc.be instead of using the issue tracker.

## Credits

- [Timothy De Cort][link-author]
- [James Ratcliffe][link-james-ratcliffe] (https://github.com/jamesratcliffe/ls-retail-guzzle)
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ls-query-parameters]: https://developers.lightspeedhq.com/retail/introduction/parameters/
[ls-docs]: https://developers.lightspeedhq.com/retail/introduction/introduction/
[ls-docs-scopes]: https://developers.lightspeedhq.com/retail/authentication/scopes
[ls-docs-mutators]: https://laravel.com/docs/eloquent-mutators#defining-an-accessor
[ls-client-portal-register]: https://cloud.lightspeedapp.com/oauth/register.php
[ls-client-portal]: https://cloud.lightspeedapp.com/oauth/update.php
[ls-added-resources]: https://github.com/timothydc/laravel-lightspeed-retail-api/tree/master/src/Services/Lightspeed
[laravel-docs-collections]: https://laravel.com/docs/7.x/collections
[ico-version]: https://img.shields.io/packagist/v/timothydc/laravel-lightspeed-retail-api.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/timothydc/laravel-lightspeed-retail-api.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/timothydc/laravel-lightspeed-retail-api/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/timothydc/laravel-lightspeed-retail-api
[link-downloads]: https://packagist.org/packages/timothydc/laravel-lightspeed-retail-api
[link-author]: https://github.com/timothydc
[link-contributors]: ../../contributors
[link-james-ratcliffe]: https://github.com/jamesratcliffe
