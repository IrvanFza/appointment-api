<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends BaseController
{
    /**
     * Display a listing of the events.
     */
    public function index(Request $request): JsonResponse
    {
        $query = auth('api')->user()->events();
        
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // TODO: Add more filters or sorting
        
        // Paginate the results
        $perPage = $request->get('per_page', 10);
        $events = $query->paginate($perPage);

        return $this->sendSuccessResponse(
            [
                'items' => $events->items(),
                'pagination' => [
                    'total' => $events->total(),
                    'per_page' => $events->perPage(),
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage()
                ],
            ],
            'Events fetched successfully'
        );
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request): JsonResponse
    {
        $rules = Event::validationRules();
        unset($rules['user_id']); // Remove user_id from validation rules

        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $data['user_id'] = auth('api')->id();

        $event = Event::create($data);

        return $this->sendSuccessResponse(
            $event->toArray(),
            'Event created successfully',
            201
        );
    }

    /**
     * Display the specified event.
     */
    public function show(string $id): JsonResponse
    {
        if (!Str::isUuid($id)) {
            return $this->sendNotFound('Event not found');
        }

        $event = Event::find($id);

        if (!$event) {
            return $this->sendNotFound('Event not found');
        }

        if ($event->user_id !== auth('api')->id()) {
            return $this->sendUnauthorized('You do not have permission to view this event');
        }

        return $this->sendSuccessResponse(
            $event->toArray(),
            'Event fetched successfully'
        );
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        if (!Str::isUuid($id)) {
            return $this->sendNotFound('Event not found');
        }

        $event = Event::find($id);

        if (!$event) {
            return $this->sendNotFound('Event not found');
        }

        if ($event->user_id !== auth('api')->id()) {
            return $this->sendUnauthorized('You do not have permission to update this event');
        }

        $allRules = Event::validationRules();
        unset($allRules['user_id']);
        
        $rules = array_intersect_key($allRules, $request->all());
        
        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $event->update($request->only(array_keys($rules)));

        return $this->sendSuccessResponse(
            $event->fresh()->toArray(),
            'Event updated successfully'
        );
    }

    /**
     * Remove the specified event.
     */
    public function destroy(string $id): JsonResponse
    {
        if (!Str::isUuid($id)) {
            return $this->sendNotFound('Event not found');
        }
        
        $event = Event::find($id);

        if (!$event) {
            return $this->sendNotFound('Event not found');
        }

        if ($event->user_id !== auth('api')->id()) {
            return $this->sendUnauthorized('You do not have permission to delete this event');
        }

        $event->delete();

        return $this->sendSuccessResponse(
            [],
            'Event deleted successfully'
        );
    }
} 