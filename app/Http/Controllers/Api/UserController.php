<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function profile()
    {
        return $this->sendSuccessResponse(
            auth('api')->user()->toArray(),
            'User profile fetched successfully'
        );
    }
} 