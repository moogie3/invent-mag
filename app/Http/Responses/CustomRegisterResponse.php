<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse;

class CustomRegisterResponse implements RegisterResponse
{
    public function toResponse($request)
    {
        return redirect()->route('login')->with('status', 'Registration successful! Please log in.');
    }
}