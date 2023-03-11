<?php

namespace App\Http\Middleware;

use ErrorException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            notify()->error("Please log in first to use this feature!");
            $prevPage = '';
            try {
                $prevPage = $request->session()->get('_previous')['url'];
            } catch(ErrorException $e) {}
            return $prevPage;
        }
    }
}
