<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Features;

class CustomRegisterResponse implements RegisterResponse
{
    public function toResponse($request)
    {
        if (in_array(Features::emailVerification(), config('fortify.features')) && ! $request->user()->hasVerifiedEmail()) {
            return $request->wantsJson()
                        ? new JsonResponse('', 202)
                        : redirect()->route('verification.notice');
        }

        return $request->wantsJson()
                    ? new JsonResponse('', 201)
                    : redirect()->route('admin.dashboard')->with('success', __('messages.register_success'));
    }
}
