<?php

namespace App\Http\Controllers;

use App\Models\SubSpecialty;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SubSpecialtyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getSubSpecialtyBySpecialtyid','getAllSubSpecialties','getFeaturedSpecialties']]);
    }
    
    public function index(Request $request)
    {
        // Check if a country_id is provided
        $countryId = $request->header('country_id');
        if (!is_null($countryId)) {
            // Filter specialties by the provided country_id
            $sub_specialties = SubSpecialty::where('country_id', $countryId)->with('country','files')->get();
        } else {
            // Return all specialties if no country_id is provided
            $sub_specialties = SubSpecialty::with('country','files')->get();
        }

        return response()->json($sub_specialties);
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
    
            // Initialize a new sub_specialty instance
            $sub_specialty = new SubSpecialty();
    
            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description', 'overview',
                'seo_title', 'seo_keywords', 'seo_description',
            ];
    
            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $subSpecialtyField = json_decode($request->$field, true);
    
                // Set translation for each locale
                foreach ($subSpecialtyField as $locale => $value) {
                    $sub_specialty->setTranslation($field, $locale, $value);
                }
            }
    
            // Set other fields
            $sub_specialty->status = $request->status;
            $sub_specialty->featured = $request->featured;
            $sub_specialty->country_id = $request->country_id;
            $sub_specialty->specialtie_id = $request->specialtie_id;
            $sub_specialty->robots = $request->robots;
    
            // Persist the sub_specialty instance into the database
            $sub_specialty->save();
    
            // Attach icon and thumb files if provided
            if ($request->has('thumb')) {
                $sub_specialty->files()->attach($request->thumb, ['type' => 'thumb']);
            }
    
            if ($request->has('icon')) {
                $sub_specialty->files()->attach($request->icon, ['type' => 'icon']);
            }
    
            return response()->json($sub_specialty);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }
    

    public function show(SubSpecialty $subSpecialty)
    {
        // Load relationships
        $subSpecialty->load('files');
    
        // Initialize IDs and URLs
        $thumbId = null;
        $iconId = null;
        $thumbUrl = '';
        $iconUrl = '';
    
        // Loop through the files to find thumb and icon
        foreach ($subSpecialty->files as $file) {
            if ($file->pivot->type == 'thumb') {
                $thumbId = $file->id;  // Store the thumb ID
                $thumbUrl = $file->file_url; // Store the thumb URL
            } elseif ($file->pivot->type == 'icon') {
                $iconId = $file->id;  // Store the icon ID
                $iconUrl = $file->file_url; // Store the icon URL
            }
        }
    
        // Prepare response data
        $responseData = $subSpecialty->toArray();
        $responseData['thumb_id'] = $thumbId;
        $responseData['icon_id'] = $iconId;
        $responseData['thumb_url'] = $thumbUrl;
        $responseData['icon_url'] = $iconUrl;
        
        unset($responseData['files']);
    
        return response()->json($responseData);
    }
    
    
    public function update(Request $request, SubSpecialty $subSpecialty)
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
                    $subSpecialtyField = json_decode($request->$field, true);
    
                    foreach ($subSpecialtyField as $locale => $value) {
                        $subSpecialty->setTranslation($field, $locale, $value);
                    }
                }
            }
    
            $subSpecialty->status = $request->status;
            $subSpecialty->featured = $request->featured;
            $subSpecialty->country_id = $request->country_id;
            $subSpecialty->specialtie_id = $request->specialtie_id;
            $subSpecialty->robots = $request->robots;
    
            $subSpecialty->save();
    
            // Update thumb and icon if provided
            if ($request->has('thumb')) {
                $subSpecialty->files()->syncWithoutDetaching([$request->thumb => ['type' => 'thumb']]);
            }
            if ($request->has('icon')) {
                $subSpecialty->files()->syncWithoutDetaching([$request->icon => ['type' => 'icon']]);
            }
    
            // Prepare response data
            $responseData = $subSpecialty->toArray();
            
            // Get the updated thumb and icon details
            $subSpecialty->load('files');
            $thumbId = null;
            $iconId = null;
            $thumbUrl = '';
            $iconUrl = '';
    
            foreach ($subSpecialty->files as $file) {
                if ($file->pivot->type == 'thumb') {
                    $thumbId = $file->id;  // Store the thumb ID
                    $thumbUrl = $file->file_url; // Store the thumb URL
                } elseif ($file->pivot->type == 'icon') {
                    $iconId = $file->id;  // Store the icon ID
                    $iconUrl = $file->file_url; // Store the icon URL
                }
            }
    
            $responseData['thumb_id'] = $thumbId;
            $responseData['icon_id'] = $iconId;
            $responseData['thumb_url'] = $thumbUrl;
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
        $subSpecialty = SubSpecialty::find($id);
        if (!$subSpecialty) {
            return response()->json(['message' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $subSpecialty->delete(); // Soft delete the country

        return response()->json(['message' => 'Item soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $subSpecialty = SubSpecialty::withTrashed()->find($id);
        if (!$subSpecialty) {
            return response()->json(['message' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        if ($subSpecialty->trashed()) {
            $subSpecialty->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Item permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Item is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getSubSpecialtyBySpecialtyid($specialty_id)
    {
        $subSpecialty = SubSpecialty::where('status',1)
                                    ->where('specialtie_id',$specialty_id)
                                    ->get()
            ->map(function ($val){
    
                return [
                    'id'=>$val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                   
                ];
            });

        return response()->json([
            'data'=>$subSpecialty
        ]);

    }

    public function getAllSubSpecialties()
    {
        try {
            // Fetch all specialties along with their files
            $specialties = SubSpecialty::where('status', 1)->with('files')->get();

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
            $specialties = SubSpecialty::where('status', 1)->where('featured',1)->with('files')->get();

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
