<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SpecialtyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getAllSpecialties', 'getFeaturedSpecialties']]);
    }
    public function index(Request $request)
    {
        // Check if a country_id is provided
        $countryId = $request->header('country_id');
        if (!is_null($countryId)) {
            // Filter specialties by the provided country_id
            $specialties = Specialty::where('country_id', $countryId)->with('country', 'files')->get();
        } else {
            // Return all specialties if no country_id is provided
            $specialties = Specialty::with('country', 'files')->get();
        }

        return response()->json($specialties);
    }



    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'name' => 'required|json',
                'slug' => 'required|json',
                'description' => 'required|json',
                'overview' => 'required|json',
                'seo_title' => 'nullable|json',
                'seo_keywords' => 'nullable|json',
                'seo_description' => 'nullable|json'
            ]);

            // Initialize a new doctor instance
            $specialty = new Specialty();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description', 'overview',
                'seo_title', 'seo_keywords', 'seo_description',

            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $specialtyField = json_decode($request->$field, true);

                // Validate English translation
                if (!isset($specialtyField['en']) || !is_string($specialtyField['en'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Validation failed',
                        'errors' => ['English ' . $field . ' is required and must be a string'],
                    ], 422);
                }

                foreach ($specialtyField as $locale => $value) {
                    $specialty->setTranslation($field, $locale, $value);
                }
            }

            // if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            //     $blog->addMediaFromRequest('banner')->toMediaCollection('banner');
            // }

            $specialty->status = $request->status;
            $specialty->featured = $request->featured;

            $specialty->country_id = $request->country_id;
            $specialty->robots = $request->robots;

            // Persist the doctor instance into the database
            $specialty->save();
            $specialty->files()->attach($request->thumb, ['type' => 'thumb']);
            $specialty->files()->attach($request->icon, ['type' => 'icon']);

            return response()->json($specialty);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function show(Specialty $specialty)
    {
        // Load files relationship
        $specialty->load('files');

        // Initialize IDs and URLs
        $thumbId = null;
        $thumbUrl = null;
        $iconId = null;
        $iconUrl = null;

        // Loop through the files to find thumb and icon
        foreach ($specialty->files as $file) {
            if ($file->pivot->type == 'thumb') {
                $thumbId = $file->id;
                $thumbUrl = $file->file_url;
            } elseif ($file->pivot->type == 'icon') {
                $iconId = $file->id;
                $iconUrl = $file->file_url;
            }
        }

        // Prepare response data
        $responseData = $specialty->toArray();
        $responseData['thumb_id'] = $thumbId;
        $responseData['thumb_url'] = $thumbUrl;
        $responseData['icon_id'] = $iconId;
        $responseData['icon_url'] = $iconUrl;
        unset($responseData['files']); // Optionally remove the files relationship from the response

        return response()->json($responseData);
    }

    public function update(Request $request, Specialty $specialty)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|json',
                'slug' => 'required|json',
                'description' => 'sometimes|required|json',
                'overview' => 'sometimes|required|json',
                'seo_title' => 'nullable|json',
                'seo_keywords' => 'nullable|json',
                'seo_description' => 'nullable|json',
            ]);

            $translatableFields = [
                'name', 'slug', 'description', 'overview',
                'seo_title', 'seo_keywords', 'seo_description',
            ];

            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $specialtyField = json_decode($request->$field, true);

                    foreach ($specialtyField as $locale => $value) {
                        $specialty->setTranslation($field, $locale, $value);
                    }
                }
            }

            $specialty->status = $request->status;
            $specialty->featured = $request->featured;

            $specialty->country_id = $request->country_id;
            $specialty->robots = $request->robots;

            // Save the specialty instance
            $specialty->save();

            // Handle file attachments
            $specialty->files()->detach();

            if ($request->hasFile('thumb') && $request->file('thumb')->isValid()) {
                $specialty->files()->attach($request->thumb, ['type' => 'thumb']);
            }

            if ($request->hasFile('icon') && $request->file('icon')->isValid()) {
                $specialty->files()->attach($request->icon, ['type' => 'icon']);
            }

            // Retrieve the updated files and their URLs
            $specialty->load('files');
            $thumbId = null;
            $thumbUrl = null;
            $iconId = null;
            $iconUrl = null;

            foreach ($specialty->files as $file) {
                if ($file->pivot->type == 'thumb') {
                    $thumbId = $file->id;
                    $thumbUrl = $file->file_url;
                } elseif ($file->pivot->type == 'icon') {
                    $iconId = $file->id;
                    $iconUrl = $file->file_url;
                }
            }

            // Prepare response data
            $responseData = $specialty->toArray();
            $responseData['thumb_id'] = $thumbId;
            $responseData['thumb_url'] = $thumbUrl;
            $responseData['icon_id'] = $iconId;
            $responseData['icon_url'] = $iconUrl;

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
        $specialty = Specialty::find($id);
        if (!$specialty) {
            return response()->json(['message' => 'Specialty not found'], Response::HTTP_NOT_FOUND);
        }

        $specialty->delete(); // Soft delete the country

        return response()->json(['message' => 'Specialty soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $specialty = Specialty::withTrashed()->find($id);
        if (!$specialty) {
            return response()->json(['message' => 'Specialty not found'], Response::HTTP_NOT_FOUND);
        }

        if ($specialty->trashed()) {
            $specialty->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Specialty permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Specialty is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getAllSpecialties()
    {
        try {
            // Fetch all specialties along with their files
            $specialties = Specialty::where('status', 1)->with('files')->get();

            // Prepare the response data
            $responseData = $specialties->map(function ($specialty) {
                // Initialize IDs and URLs for thumb and icon
               
                $thumb = null;
        
                $icon = null;

                // Loop through the files to find thumb and icon
                foreach ($specialty->files as $file) {
                    if ($file->pivot->type == 'thumb') {
                    
                        $thumb = $file->file_url;
                    } elseif ($file->pivot->type == 'icon') {
              
                        $icon = $file->file_url;
                    }
                }

                // Prepare each specialty's data
                return [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'slug' => $specialty->slug,
                    'description' => $specialty->description,
                    'thumb' => $thumb,
                    'icon' => $icon,
                ];
            });

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch specialties',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeaturedSpecialties()
    {
        try {
            // Fetch all specialties along with their files
            $specialties = Specialty::where('status', 1)->where('featured',1)->with('files')->get();

            // Prepare the response data
            $responseData = $specialties->map(function ($specialty) {
                // Initialize IDs and URLs for thumb and icon
               
                $thumb = null;
                $icon = null;
                // Loop through the files to find thumb and icon
                foreach ($specialty->files as $file) {
                    if ($file->pivot->type == 'thumb') {
                    
                        $thumb = $file->file_url;
                    } elseif ($file->pivot->type == 'icon') {
              
                        $icon = $file->file_url;
                    }
                }

                // Prepare each specialty's data
                return [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'slug' => $specialty->slug,
                    'description' => $specialty->description,
                    'thumb' => $thumb,
                    'icon' => $icon,
                ];
            });

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch specialties',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
