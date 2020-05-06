# laravel-lightspeed-retail-api

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

## Installation

Via Composer

``` bash
$ composer require timothydc/laravel-lightspeed-retail-api
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
$ php artisan vendor:publish --tag="lightspeed-retail"

$ php artisan vendor:publish --tag="lightspeed-retail:config"
$ php artisan vendor:publish --tag="lightspeed-retail:migrations"
```

The API tokens are stored in the database, by default. So run your migrations.

```bash
$ php artisan migrate
```


### Request initial access token

Before we can request an access token you need to connect your Retail POS to this app. 

You can manage the access level to your POS data via a scope. Choose a `$scope` from the options in `TimothyDC\LightspeedRetailApi\Scope`.
More information on the scopes can be found in the [documentation][ls-docs-scopes].

```php
use TimothyDC\LightspeedRetailApi\Scope;
use TimothyDC\LightspeedRetailApi\LightspeedRetailApi;

$scope = Scope::EMPLOYEE_ALL;

return LightspeedRetailApi::redirectToAuthorizationPortal($scope);
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

// get category with ID 20
$categories = LightspeedRetailApi::api()->category()->get(20);

// same as above, but better
$categories = LightspeedRetailApi::api()->category()->first(20);
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email mail@timothydc.be instead of using the issue tracker.

## Credits

- [Timothy De Cort][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ls-docs]: https://developers.lightspeedhq.com/retail/introduction/introduction/
[ls-docs-scopes]: https://developers.lightspeedhq.com/retail/authentication/scopes
[ls-client-portal-register]: https://cloud.lightspeedapp.com/oauth/register.php
[ls-client-portal]: https://cloud.lightspeedapp.com/oauth/update.php
[laravel-docs-collections]: https://laravel.com/docs/7.x/collections
[ico-version]: https://img.shields.io/packagist/v/timothydc/laravel-lightspeed-retail-api.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/timothydc/laravel-lightspeed-retail-api.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/timothydc/laravel-lightspeed-retail-api/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/timothydc/laravel-lightspeed-retail-api
[link-downloads]: https://packagist.org/packages/timothydc/laravel-lightspeed-retail-api
[link-travis]: https://travis-ci.org/timothydc/laravel-lightspeed-retail-api
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/timothydc
[link-contributors]: ../../contributors
