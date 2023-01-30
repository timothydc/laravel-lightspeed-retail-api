<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use TimothyDC\LightspeedRetailApi\Facades\LightspeedRetailApi;

class SaveAccessTokenController extends Controller
{
    public function __invoke(Request $request): \Illuminate\Http\RedirectResponse
    {
        // save access token
        LightspeedRetailApi::api()->startUpClient($request->get('code'));

        // redirect to home
        return redirect()->to($this->redirectPath())->with('status', trans('Lightspeed Retail authentication token created'));
    }

    protected function redirectPath(): string
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo')
            ? $this->redirectTo
            : RouteServiceProvider::HOME;
    }
}
