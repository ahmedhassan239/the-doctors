<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;

use App\Models\ScheduleDay;
use App\Models\ScheduleDayTime;
use App\Models\ScheduleDayTimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DateInterval;
use DateTime;


class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schedules = Schedule::get(); // Assuming you have a 'days' relationship defined in your Schedule model
        return response()->json($schedules);
    }


    /**
     * Show the form for creating a new resource.
     */


     public function store(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'provider_id' => 'required|integer',
             'doctor_id' => 'required|integer',
             'schedule_gap' => 'required|integer',
             'schedule_meeting_time' => 'required',
             'days' => 'required|array',
         ]);
 
         if ($validator->fails()) {
             return response()->json(['errors' => $validator->errors()], 422);
         }
 
         // try {
         DB::beginTransaction();
 
         $schedule = Schedule::updateOrCreate(
             [
                 'provider_id' => $request->provider_id,
                 'doctor_id' => $request->doctor_id
             ],
             [
                 'schedule_gap' => $request->schedule_gap,
                 'schedule_meeting_time' => $request->schedule_meeting_time
             ]
         );
 
         foreach ($request->days as $day) {
 
             $scheduleDay = ScheduleDay::updateOrCreate([
                 'schedule_id' => $schedule->id,
                 'day_name' => $day['day_name'],
             ], [
                 'day_number' => $day['day_number'],
                 'status' => $day['status']
             ]);
 
             ScheduleDayTime::where('schedule_day_id', $scheduleDay->id)->delete();
 
             foreach ($day['times'] as $time) {

                 $timeStartFromObj = new DateTime($time['start_from']);
                 $timeEndToObj = new DateTime($time['end_to']);
 
                 $timeStartFrom = $timeStartFromObj->format("H:i");
                 $timeEndTo = $timeEndToObj->format("H:i");
 
 
                 $scheduleDayTime = ScheduleDayTime::create([
                     'schedule_day_id' => $scheduleDay->id,
                     'start_from' => $timeStartFrom,
                     'end_to' => $timeEndTo
                 ]);
 
                 $number_of_slots = round((abs($timeStartFromObj->getTimestamp() - $timeEndToObj->getTimestamp()) / 60) / ($request->schedule_gap + $request->schedule_meeting_time)) ;
//  dd($number_of_slots);
                 //set the time of the first slot
                 $slotStartFromObj = $timeStartFromObj; //10
                 for ($i = 0; $i < $number_of_slots; $i++) {
 
                     ScheduleDayTimeSlot::create([
                         'schedule_day_time_id' => $scheduleDayTime->id,
                         'start_from' => $slotStartFromObj->format("H:i"),
                         'end_to' => $slotStartFromObj->add(new DateInterval('PT' . (int) $request->schedule_meeting_time . 'M'))->format("H:i"),
                     ]);
 
                     $slotStartFromObj->add(new DateInterval('PT' . (int) $request->schedule_gap . 'M'));
                  }
             }
         }
 
         DB::commit();
 
         return response()->json(['message' => 'Schedule created successfully'], 200);
         // } catch (\Exception $e) {
         //     DB::rollBack();
         //     return response()->json(['error' => 'Failed to create schedule: ' . $e->getMessage()], 500);
         // }
     }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            // Retrieve the schedule with its related 'ScheduleDay' entries
            $schedule = Schedule::with(['scheduleDays'])->findOrFail($id);

            // Format and return the response
            return response()->json($schedule);
        } catch (\Exception $e) {
            // Handle the case where the schedule is not found or other exceptions
            return response()->json(['error' => 'Error retrieving schedule: ' . $e->getMessage()], 404);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $scheduleId)
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|integer',
            'doctor_id' => 'required|integer',
            'schedule_gap' => 'required|integer',
            'schedule_meeting_time' => 'required',
            'days' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // try {
        DB::beginTransaction();
        $schedule = Schedule::findOrFail($scheduleId);
        $schedule->provider_id = $request->provider_id;
        $schedule->doctor_id = $request->doctor_id;
        $schedule->schedule_gap = $request->schedule_gap;
        $schedule->schedule_meeting_time = $request->schedule_meeting_time;

        $schedule->save();
      
        foreach ($request->days as $day) {

            $scheduleDay = ScheduleDay::updateOrCreate([
                'schedule_id' => $schedule->id,
                'day_name' => $day['day_name'],
            ], [
                'day_number' => $day['day_number'],
                'status' => $day['status']
            ]);

            ScheduleDayTime::where('schedule_day_id', $scheduleDay->id)->delete();

            foreach ($day['times'] as $time) {

                $timeStartFromObj = new DateTime($time['start_from']);
                $timeEndToObj = new DateTime($time['end_to']);

                $timeStartFrom = $timeStartFromObj->format("H:i");
                $timeEndTo = $timeEndToObj->format("H:i");


                $scheduleDayTime = ScheduleDayTime::create([
                    'schedule_day_id' => $scheduleDay->id,
                    'start_from' => $timeStartFrom,
                    'end_to' => $timeEndTo
                ]);

                $number_of_slots = round((abs($timeStartFromObj->getTimestamp() - $timeEndToObj->getTimestamp()) / 60) / ($request->schedule_gap + $request->schedule_meeting_time)) ;

                //set the time of the first slot
                $slotStartFromObj = $timeStartFromObj; //10
                for ($i = 0; $i < $number_of_slots; $i++) {

                    ScheduleDayTimeSlot::create([
                        'schedule_day_time_id' => $scheduleDayTime->id,
                        'start_from' => $slotStartFromObj->format("H:i"),
                        'end_to' => $slotStartFromObj->add(new DateInterval('PT' . (int) $request->schedule_meeting_time . 'M'))->format("H:i"),
                    ]);

                    $slotStartFromObj->add(new DateInterval('PT' . (int) $request->schedule_gap . 'M'));
                 }
            }
        }

        DB::commit();

        return response()->json(['message' => 'Schedule created successfully'], 200);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json(['error' => 'Failed to create schedule: ' . $e->getMessage()], 500);
        // }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $schedule = Schedule::findOrFail($id);

            // Optional: Delete related entries first if there are dependencies
            // Assuming there's a one-to-many relationship defined as 'scheduleDays'
            // This step is necessary if your foreign key constraints prevent deletion of a schedule without first removing the related entries
            $schedule->scheduleDays()->delete();

            // Now delete the schedule itself
            $schedule->delete();

            return response()->json(['message' => 'Schedule deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle the case where the schedule is not found
            return response()->json(['error' => 'Schedule not found'], 404);
        } catch (\Exception $e) {
            // Handle other possible exceptions
            return response()->json(['error' => 'Failed to delete schedule: ' . $e->getMessage()], 500);
        }
    }
}
