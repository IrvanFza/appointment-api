<?php

namespace App\Http\Controllers\Api;

use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends BaseController
{
    private function getOrCreatePreference(array $defaults = []): UserPreference
    {
        return UserPreference::firstOrCreate(
            ['user_id' => auth('api')->id()],
            $defaults
        );
    }

    public function show(): JsonResponse
    {
        $preference = $this->getOrCreatePreference([
            'is_available' => true,
            'block_lunch_break' => false,
            'block_public_holiday' => false,
            'timezone' => config('app.timezone'),
            'lunch_break_start_time' => '12:00:00',
            'lunch_break_end_time' => '13:00:00',
        ]);

        return $this->sendSuccessResponse(
            $preference->toArray(),
            'User preferences fetched successfully'
        );
    }

    public function update(Request $request): JsonResponse
    {
        $preference = $this->getOrCreatePreference();

        $validator = validator($request->all(), UserPreference::updateRules($preference->id));

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $preference->update($validator->validated());

        return $this->sendSuccessResponse(
            $preference->fresh()->toArray(),
            'User preferences updated successfully'
        );
    }
} 