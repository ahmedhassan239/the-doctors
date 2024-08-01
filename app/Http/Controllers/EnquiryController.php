<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentDetailsToAdmin;
use App\Mail\AppointmentRequestToPatient;
use App\Mail\AppointmentRequestToPatientAr;
use App\Models\Enquiry;
use App\Models\ScheduleDayTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EnquiryController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:api', ['except' => ['store']]);
    }
    public function index()
    {
        // Eager load the service and scheduleDayTime relationships
        $enquiries = Enquiry::with(['healthcareProvider', 'scheduleDayTime', 'scheduleDayTimeSlot'])->get();
        // dd($enquiries);

        //    dd( $enquiries);


        // Transform the enquiries to include the service name and slot times
        $transformed = $enquiries->map(function ($enquiry) {
            return [
                'id' => $enquiry->id,
                'name' => $enquiry->name,
                'email' => $enquiry->email,
                'phone' => $enquiry->phone,
                'date' => $enquiry->date,
                'status' => $enquiry->status,
                'age' => $enquiry->age,
                'country' => $enquiry->country,
                'healthcarePtovider_name' => $enquiry->healthcareProvider ? $enquiry->healthcareProvider->name : null,
                'slot_start_from' => $enquiry->scheduleDayTimeSlot ? $enquiry->scheduleDayTimeSlot->start_from : null,
                'slot_end_to' => $enquiry->scheduleDayTimeSlot ? $enquiry->scheduleDayTimeSlot->end_to : null,
                'created_at' => $enquiry->created_at,

            ];
        });

        return response()->json($transformed);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|max:255',
                'email' => 'required|email',
                'phone' => 'required',
                'date' => 'required',
                'healthcare_provider_id' => 'required|integer',
                'doctor_id' => 'required|integer',
                'slot_id' => 'required|integer',
                'lang' => 'required',
                'age' => 'required',
                'country' => 'required',
            ]);

            // Store the enquiry
            $enquiry = Enquiry::create($validated);

            // // Choose the appropriate Mailable based on language
            // $mailable = $request->lang == "en" ? new AppointmentRequestToPatient($enquiry) : new AppointmentRequestToPatientAr($enquiry);

            // // Send email to patient
            // Mail::to($request->email)->send($mailable);

            // // Send email to admin
            // Mail::to('admin@ridentdentalcenters.com')->send(new AppointmentDetailsToAdmin($enquiry));

            // Return the created enquiry
            return response()->json($enquiry, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return a JSON response with errors
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // General error handling (optional)
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Enquiry $enquiry)
    {
        return response()->json($enquiry);
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([

            'status' => 'nullable'
        ]);

        $enquiry->update($validated);
        return response()->json($enquiry);
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();
        return response()->json(null, 204);
    }
}
