<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\HealthCareProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DoctorController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['getAllBlogs', 'getSingleBlog','getFeaturedBlogs']]);
    // }
    public function index(Request $request)
    {
        // Check if a country_id is provided
        $countryId = $request->header('country_id');
        if (!is_null($countryId)) {

            // Filter specialties by the provided country_id
            $doctors = Doctor::where('country_id', $countryId)->with('country', 'files', 'specialty')->get();
        } else {
            // Return all specialties if no country_id is provided
            $doctors = Doctor::with('country', 'files', 'specialty')->get();
        }

        return response()->json($doctors);
    }

    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                // Add your validation rules here
            ]);
    
            // Initialize a new doctor instance
            $doctor = new Doctor();
    
            // Define the translatable fields
            $translatableFields = [
                'name', 'slug',
                'description', 'overview', 'seo_title', 'seo_keywords', 'seo_description',
            ];
    
            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $doctor->setTranslation($field, 'en', $request->$field['en']);
                    $doctor->setTranslation($field, 'ar', $request->$field['ar']);
                }
            }
    
            $doctor->status = $request->status;
            $doctor->featured = $request->featured;
            $doctor->country_id = $request->country_id;
            $doctor->specialtie_id = $request->specialtie_id;
            $doctor->sub_specialtie = $request->sub_specialtie;
            $doctor->robots = $request->robots;
    
            // Persist the doctor instance into the database
            $doctor->save();
            $doctor->files()->attach($request->thumb, ['type' => 'thumb']);
            $gallery = json_decode($request->gallery, true);
            if (is_array($gallery)) {
                foreach ($gallery as $galleryId) {
                    $doctor->files()->attach($galleryId, ['type' => 'gallery']);
                }
            }
    
            // Save doctor-healthcare provider relationships
            if ($request->has('healthcare_providers')) {
                $healthcareProviderIds = explode(',', $request->healthcare_providers);
                if (is_array($healthcareProviderIds)) {
                    foreach ($healthcareProviderIds as $providerId) {
                        $doctor->healthcareProviders()->attach(trim($providerId));
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for healthcare providers.',
                    ], 422);
                }
            }
    
            // Save doctor-branch relationships
            if ($request->has('branche_ids')) {
                $brancheIds = explode(',', $request->branche_ids);
                if (is_array($brancheIds)) {
                    foreach ($brancheIds as $brancheId) {
                        $doctor->branches()->attach(trim($brancheId), ['healthcare_provider_id' => $request->healthcare_providers]);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for branches.',
                    ], 422);
                }
            }
    
            return response()->json($doctor);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }
    
    
    
    
    public function show(Doctor $doctor)
    {
        // Load relationships
        $doctor->load('files', 'healthcareProviders');
        
        // Initialize IDs and URLs
        $bannerId = null;
        $thumbId = null;
        $bannerUrl = '';
        $thumbUrl = '';
        
        // Loop through the files to find banner and thumb
        foreach ($doctor->files as $file) {
            if ($file->pivot->type == 'banner') {
                $bannerId = $file->id;  // Store the banner ID
                $bannerUrl = $file->file_url;
            } elseif ($file->pivot->type == 'thumb') {
                $thumbId = $file->id;  // Store the thumb ID
                $thumbUrl = $file->file_url;
            }
        }
        
        // Prepare response data
        $responseData = $doctor->toArray();
        $responseData['banner_id'] = $bannerId;
        $responseData['thumb_id'] = $thumbId;
        $responseData['banner_url'] = $bannerUrl;
        $responseData['thumb_url'] = $thumbUrl;
        
        // Add healthcare provider IDs and branch IDs to the response data
        $healthcareProviders = [];
        $branches = [];
        foreach ($doctor->healthcareProviders as $provider) {
            $healthcareProviders[] = $provider->id;
            $branchIds = DB::table('healthcare_provider_branch')
                ->where('doctor_id', $doctor->id)
                ->where('healthcare_provider_id', $provider->id)
                ->pluck('branche_id')
                ->toArray();
            $branches = array_merge($branches, $branchIds);
        }
        
        // Convert healthcare provider IDs to a comma-separated string
        $responseData['healthcare_provider_ids'] = count($healthcareProviders) > 0 ? implode(',', $healthcareProviders) : null;
        $responseData['branch_ids'] = count($branches) > 0 ? array_values(array_unique($branches)) : null;
        
        unset($responseData['files']);
        unset($responseData['healthcare_providers']); // Remove healthcare_providers array
        
        // Remove unnecessary fields
        unset($responseData['healthcare_provider_id']);
        unset($responseData['branche_ids']);
        
        return response()->json($responseData);
    }
    
    
    
    public function update(Request $request, Doctor $doctor)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                // Add your validation rules here
            ]);
    
            // Define the translatable fields
            $translatableFields = [
                'name', 'slug',
                'description', 'overview', 'seo_title', 'seo_keywords', 'seo_description',
            ];
    
            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $doctor->setTranslation($field, 'en', $request->$field['en']);
                    $doctor->setTranslation($field, 'ar', $request->$field['ar']);
                }
            }
    
            $doctor->status = $request->status;
            $doctor->featured = $request->featured;
            $doctor->country_id = $request->country_id;
            $doctor->specialtie_id = $request->specialtie_id;
            $doctor->sub_specialtie = $request->sub_specialtie;
            $doctor->robots = $request->robots;
    
            // Persist the doctor instance into the database
            $doctor->save();
    
            $doctor->files()->detach();
            $doctor->files()->attach($request->thumb, ['type' => 'thumb']);
            $gallery = json_decode($request->gallery, true);
            if (is_array($gallery)) {
                foreach ($gallery as $galleryId) {
                    $doctor->files()->attach($galleryId, ['type' => 'gallery']);
                }
            }
    
            // Save doctor-healthcare provider relationships
            if ($request->has('healthcare_providers')) {
                $healthcareProviderIds = explode(',', $request->healthcare_providers);
                if (is_array($healthcareProviderIds)) {
                    $doctor->healthcareProviders()->sync(array_map('trim', $healthcareProviderIds));
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for healthcare providers.',
                    ], 422);
                }
            }
    
            // Save doctor-branch relationships
            if ($request->has('branche_ids')) {
                $brancheIds = explode(',', $request->branche_ids);
                if (is_array($brancheIds)) {
                    foreach ($brancheIds as $brancheId) {
                        $doctor->branches()->syncWithoutDetaching([trim($brancheId) => ['healthcare_provider_id' => $request->healthcare_providers]]);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for branches.',
                    ], 422);
                }
            }
    
            // Retrieve thumb URLs
            $doctor->load('files');
            $thumbId = null;
            $thumbUrl = '';
    
            // Loop through the files to find thumb
            foreach ($doctor->files as $file) {
                if ($file->pivot->type == 'thumb') {
                    $thumbId = $file->id;  // Store the thumb ID
                    $thumbUrl = $file->file_url;
                }
            }
    
            // Prepare response data
            $responseData = $doctor->toArray();
            $responseData['thumb_id'] = $thumbId;
            $responseData['thumb_url'] = $thumbUrl;
            unset($responseData['files']);
    
            return response()->json($responseData);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }
    


    public function softDelete($id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor Provider not found'], Response::HTTP_NOT_FOUND);
        }

        $doctor->delete(); // Soft delete the country

        return response()->json(['message' => 'Doctor Provider soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $doctor = Doctor::withTrashed()->find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor Provider not found'], Response::HTTP_NOT_FOUND);
        }

        if ($doctor->trashed()) {
            $doctor->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Doctor Provider permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Doctor Provider is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }


    public function singleDoctor(){
        
    }
}
