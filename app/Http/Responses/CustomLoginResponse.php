<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CustomLoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();
        $rememberLastPage = $user->system_settings['remember_last_page'] ?? false;

        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 200);
        }

        $intendedUrl = Session::get('url.intended', config('fortify.home'));

        if ($rememberLastPage) {
            return redirect()->intended($intendedUrl)->with('success', 'Login successful!');
        } else {
            // Clear the intended URL if not remembering last page
            Session::forget('url.intended');
            return redirect(config('fortify.home'))->with('success', 'Login successful!');
        }
    }
}
