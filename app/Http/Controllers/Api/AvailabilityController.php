<?php

namespace App\Http\Controllers\Api;

use App\Models\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AvailabilityController extends BaseController
{
    public function index(): JsonResponse
    {
        $availabilities = auth('api')->user()->availabilities()->orderBy('day_of_week')->orderBy('start_time')->get();

        return $this->sendSuccessResponse(
            $availabilities->toArray(),
            'Availabilities fetched successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $rules = Availability::validationRules();
        unset($rules['user_id']); // Remove user_id from validation rules

        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $data['user_id'] = auth('api')->id();

        $availability = Availability::create($data);

        return $this->sendSuccessResponse(
            $availability->toArray(),
            'Availability created successfully',
            201
        );
    }

    public function show(string $id): JsonResponse
    {
        if (!Str::isUuid($id)) {
            return $this->sendNotFound('Availability not found');
        }

        $availability = Availability::find($id);

        if (!$availability) {
            return $this->sendNotFound('Availability not found');
        }

        if ($availability->user_id !== auth('api')->id()) {
            return $this->sendUnauthorized('You do not have permission to view this availability');
        }

        return $this->sendSuccessResponse(
            $availability->toArray(),
            'Availability fetched successfully'
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if (!Str::isUuid($id)) {
            return $this->sendNotFound('Availability not found');
        }

        $availability = Availability::find($id);

        if (!$availability) {
            return $this->sendNotFound('Availability not found');
        }

        if ($availability->user_id !== auth('api')->id()) {
            return $this->sendUnauthorized('You do not have permission to update this availability');
        }

        $allRules = Availability::validationRules();
        unset($allRules['user_id']);
        
        $rules = array_intersect_key($allRules, $request->all());
        
        if ($request->has('end_time') && !$request->has('start_time')) {
            $rules['end_time'] = ['required', 'date_format:H:i', 'after:' . $availability->start_time->format('H:i')];
        } else if ($request->has('start_time') && !$request->has('end_time')) {
            $rules['start_time'] = ['required', 'date_format:H:i', 'before:' . $availability->end_time->format('H:i')];
        }

        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $availability->update($request->only(array_keys($rules)));

        return $this->sendSuccessResponse(
            $availability->fresh()->toArray(),
            'Availability updated successfully'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        if (!Str::isUuid($id)) {
            return $this->sendNotFound('Availability not found');
        }
        
        $availability = Availability::find($id);

        if (!$availability) {
            return $this->sendNotFound('Availability not found');
        }

        if ($availability->user_id !== auth('api')->id()) {
            return $this->sendUnauthorized('You do not have permission to delete this availability');
        }

        $availability->delete();

        return $this->sendSuccessResponse(
            [],
            'Availability deleted successfully'
        );
    }
} 