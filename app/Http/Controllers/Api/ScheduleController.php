<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        $rules = Schedule::validationRules();
        // user_id and status will be set by the controller, no need to validate them
        unset($rules['user_id'], $rules['status']);
        
        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        
        $event = Event::find($data['event_id']);
        if (!$event) {
            return $this->sendNotFound('Event not found');
        }
        
        $data['user_id'] = $event->user_id;
        $data['status'] = 'confirmed';
        
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        
        if ($this->hasTimeConflict($event->user_id, null, $startTime, $endTime)) {
            return $this->sendErrorResponse(
                'Time slot is already booked',
                ['time_conflict' => 'The selected time slot conflicts with an existing appointment'],
                422
            );
        }

        $schedule = Schedule::create($data);

        return $this->sendSuccessResponse(
            $schedule->toArray(),
            'Schedule created successfully',
            201
        );
    }

    public function show(string $serial): JsonResponse
    {
        $schedule = $this->getScheduleBySerial($serial);
        if ($schedule instanceof JsonResponse) {
            return $schedule;
        }

        return $this->sendSuccessResponse(
            $schedule->toArray(),
            'Schedule fetched successfully'
        );
    }

    public function update(Request $request, string $serial): JsonResponse
    {
        $schedule = $this->getScheduleBySerial($serial);
        if ($schedule instanceof JsonResponse) {
            return $schedule;
        }
        
        // Only allow updating specific fields
        $allowedFields = ['start_time', 'end_time', 'client_name', 'client_email'];
        $rules = array_intersect_key(Schedule::validationRules(), array_flip($allowedFields));
        
        $rules = array_intersect_key($rules, $request->all());
        
        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }
        
        $data = $validator->validated();
        
        if (isset($data['start_time']) || isset($data['end_time'])) {
            $start = $data['start_time'] ?? $schedule->start_time;
            $end = $data['end_time'] ?? $schedule->end_time;
            
            if ($this->hasTimeConflict($schedule->user_id, $schedule->id, $start, $end)) {
                return $this->sendErrorResponse(
                    'Time slot is already booked',
                    ['time_conflict' => 'The selected time slot conflicts with an existing appointment'],
                    422
                );
            }
        }

        $schedule->update($data);

        return $this->sendSuccessResponse(
            $schedule->fresh()->toArray(),
            'Schedule updated successfully'
        );
    }

    public function cancel(string $serial): JsonResponse
    {
        $schedule = $this->getScheduleBySerial($serial);
        if ($schedule instanceof JsonResponse) {
            return $schedule;
        }
        
        if ($schedule->status === 'cancelled') {
            return $this->sendErrorResponse('Schedule is already cancelled', [], 422);
        }
        
        $schedule->update(['status' => 'cancelled']);
        
        return $this->sendSuccessResponse(
            $schedule->fresh()->toArray(),
            'Schedule cancelled successfully'
        );
    }
    
    /**
     * Check if there's a time conflict with existing schedules
     * 
     * @param string $userId The user ID to check schedules for
     * @param string|null $excludeId Schedule ID to exclude from the check (for updates)
     * @param string $startTime Start time to check
     * @param string $endTime End time to check
     * @return bool True if there's a conflict, false otherwise
     */
    private function hasTimeConflict(string $userId, ?string $excludeId, string $startTime, string $endTime): bool
    {
        $query = "
            SELECT id FROM schedules 
            WHERE user_id = ? 
            AND status = 'confirmed'
            AND tsrange(start_time::timestamp, end_time::timestamp) && tsrange(?::timestamp, ?::timestamp)
        ";
        
        $params = [$userId, $startTime, $endTime];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $existingSchedule = DB::select($query, $params);
        
        return !empty($existingSchedule);
    }
    
    /**
     * Get schedule by serial
     * 
     * @param string $serial Schedule serial
     * @return Schedule|JsonResponse Schedule object if found, JsonResponse otherwise
     */
    private function getScheduleBySerial(string $serial): JsonResponse|Schedule
    {
        $schedule = Schedule::where('serial', $serial)->first();

        if (!$schedule) {
            return $this->sendNotFound('Schedule not found');
        }
        
        return $schedule;
    }
} 