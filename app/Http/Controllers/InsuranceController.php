<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class InsuranceController extends Controller
{
    public function index(Request $request)
    {
        // Attempt to retrieve country_id from request headers
        $countryId = $request->header('country_id');
        if (!is_null($countryId)) {
            // Filter insurances by the provided country_id from the header
            $insurances = Insurance::where('country_id', $countryId)
                                    ->with('country', 'files')->get();
        } else {
            // Return all insurances if no country_id is provided in the header
            $insurances = Insurance::with('country', 'files')->get();
        }
    
        return response()->json($insurances);
    }

    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                // 'name' => 'required|json',
                // 'slug' => 'required|json',
                // 'description' => 'required|json',
                // 'overview' => 'required|json',
                // 'seo_title' => 'nullable|json',
                // 'seo_keywords' => 'nullable|json',
                // 'seo_description' => 'nullable|json'
            ]);

            // Initialize a new doctor instance
            $insurance = new Insurance();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug',
                'description', 'overview', 'seo_title', 'seo_keywords', 'seo_description',
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $insuranceField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($insuranceField as $locale => $value) {
                    $insurance->setTranslation($field, $locale, $value);
                }
            }

            // if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            //     $blog->addMediaFromRequest('banner')->toMediaCollection('banner');
            // }


            $insurance->status = $request->status;

            $insurance->country_id = $request->country_id;
            $insurance->governorate_id  = $request->governorate_id;
            $insurance->area_id  = $request->area_id;
            $insurance->robots = $request->robots;
          

            // Persist the doctor instance into the database
            $insurance->save();
            $insurance->files()->attach($request->thumb, ['type' => 'thumb']);
            $insurance->files()->attach($request->banner, ['type' => 'banner']);
            // $gallery = json_decode($request->gallery, true);
            // if (is_array($gallery)) {
            //     foreach ($gallery as $galleryId) {
            //         $insurance->files()->attach($galleryId, ['type' => 'gallery']);
            //     }
            // }   

            return response()->json($insurance);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function show(Insurance $insurance)
    {
        // Initialize IDs and URLs
        $bannerId = null;
        $thumbId = null;
        $bannerUrl = '';
        $thumbUrl = '';

        // Loop through the files to find banner and thumb
        foreach ($insurance->files as $file) {
            if ($file->pivot->type == 'banner') {
                $bannerId = $file->id;  // Store the banner ID
                $bannerUrl = $file->file_url;
            } elseif ($file->pivot->type == 'thumb') {
                $thumbId = $file->id;  // Store the thumb ID
                $thumbUrl = $file->file_url;
            }
        }

        // Prepare response data
        $responseData = $insurance->toArray();
        $responseData['banner_id'] = $bannerId;
        $responseData['thumb_id'] = $thumbId;
        $responseData['banner_url'] = $bannerUrl;
        $responseData['thumb_url'] = $thumbUrl;
        unset($responseData['files']);

        return response()->json($responseData);
    }

    public function update(Request $request, Insurance $insurance)
    {
        try {
            $validatedData = $request->validate([
                // 'name' => 'required|json',
                // 'slug' => 'required|json',
                // 'description' => 'sometimes|required|json',
                // 'overview' => 'sometimes|required|json',
                // 'seo_title' => 'nullable|json',
                // 'seo_keywords' => 'nullable|json',
                // 'seo_description' => 'nullable|json',
                // 'status' => 'required', // Assuming status is required
                // 'featured' => 'required', // Assuming featured is required
                // 'banner' => 'required', // Assuming banner file ID is required
                // 'thumb' => 'required', // Assuming thumb file ID is required
            ]);

            $translatableFields = [
                'name', 'slug',
                'description', 'overview', 'seo_title', 'seo_keywords', 'seo_description',
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $insuranceField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($insuranceField as $locale => $value) {
                    $insurance->setTranslation($field, $locale, $value);
                }
            }

            // if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            //     $blog->addMediaFromRequest('banner')->toMediaCollection('banner');
            // }

            $insurance->status = $request->status;

            $insurance->country_id = $request->country_id;
            $insurance->governorate_id  = $request->governorate_id;
            $insurance->area_id  = $request->area_id;
            $insurance->robots = $request->robots;
          

            // Persist the doctor instance into the database
            $insurance->save();

            $insurance->files()->detach();

            $insurance->files()->attach($request->banner, ['type' => 'banner']);
            $insurance->files()->attach($request->thumb, ['type' => 'thumb']);

            // Retrieve banner and thumb URLs
            $insurance->load('files');
            // dd($blog->load('files'));// Reload the files relationship
            $bannerId = null;
            $thumbId = null;
            $bannerUrl = '';
            $thumbUrl = '';

            // Loop through the files to find banner and thumb
            foreach ($insurance->files as $file) {
                if ($file->pivot->type == 'banner') {
                    $bannerId = $file->id;  // Store the banner ID
                    $bannerUrl = $file->file_url;
                } elseif ($file->pivot->type == 'thumb') {
                    $thumbId = $file->id;  // Store the thumb ID
                    $thumbUrl = $file->file_url;
                }
            }

            // Prepare response data
            $responseData = $insurance->toArray();
            $responseData['banner_id'] = $bannerId;
            $responseData['thumb_id'] = $thumbId;
            $responseData['banner_url'] = $bannerUrl;
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
        $insurance = Insurance::find($id);
        if (!$insurance) {
            return response()->json(['message' => 'Insurance Provider not found'], Response::HTTP_NOT_FOUND);
        }

        $insurance->delete(); // Soft delete the country

        return response()->json(['message' => 'Insurance Provider soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $insurance = Insurance::withTrashed()->find($id);
        if (!$insurance) {
            return response()->json(['message' => 'Insurance Provider not found'], Response::HTTP_NOT_FOUND);
        }

        if ($insurance->trashed()) {
            $insurance->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Insurance Provider permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Insurance Provider is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }
}
