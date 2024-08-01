<?php

namespace App\Http\Controllers;

use App\Models\Branche;
use App\Models\File;
use App\Models\HealthcareProvider;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class BrancheController extends Controller
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
            $branches = Branche::where('country_id', $countryId)->with('country', 'files')->get();
        } else {
            // Return all specialties if no country_id is provided
            $branches = Branche::with('country', 'files')->get();
        }

        return response()->json($branches);
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

            // Initialize a new branch instance
            $branch = new Branche();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug',
                'description', 'overview', 'seo_title', 'seo_keywords', 'seo_description',
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $branchField = json_decode($request->$field, true);
                foreach ($branchField as $locale => $value) {
                    $branch->setTranslation($field, $locale, $value);
                }
            }

            // Set other branch fields
            $branch->status = $request->status;
            $branch->featured = $request->featured;
            $branch->country_id = $request->country_id;
            $branch->governorate_id  = $request->governorate_id;
            $branch->area_id  = $request->area_id;
            $branch->location = $request->location;
            $branch->robots = $request->robots;

            // Persist the branch instance into the database
            $branch->save();

            // Attach files
            if ($request->has('thumb') && File::find($request->thumb)) {
                $branch->files()->attach($request->thumb, ['type' => 'thumb']);
            }

            if ($request->has('banner') && File::find($request->banner)) {
                $branch->files()->attach($request->banner, ['type' => 'banner']);
            }

            $gallery = json_decode($request->gallery, true);
            if (is_array($gallery)) {
                foreach ($gallery as $galleryId) {
                    if (File::find($galleryId)) {
                        $branch->files()->attach($galleryId, ['type' => 'gallery']);
                    }
                }
            }

            // Attach the branch to a healthcare provider
            if ($request->has('healthcare_provider_id')) {
                $healthcareProvider = HealthcareProvider::find($request->healthcare_provider_id);
                if ($healthcareProvider) {
                    $branch->healthcareProviders()->attach($healthcareProvider->id);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Healthcare Provider not found.',
                    ], 404);
                }
            }

            return response()->json($branch);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Branche $branch)
    {
        // Initialize IDs and URLs
        $bannerId = null;
        $thumbId = null;
        $bannerUrl = '';
        $thumbUrl = '';

        // Loop through the files to find banner and thumb
        foreach ($branch->files as $file) {
            if ($file->pivot->type == 'banner') {
                $bannerId = $file->id;  // Store the banner ID
                $bannerUrl = $file->file_url;
            } elseif ($file->pivot->type == 'thumb') {
                $thumbId = $file->id;  // Store the thumb ID
                $thumbUrl = $file->file_url;
            }
        }

        // Prepare response data
        $responseData = $branch->toArray();
        $responseData['banner_id'] = $bannerId;
        $responseData['thumb_id'] = $thumbId;
        $responseData['banner_url'] = $bannerUrl;
        $responseData['thumb_url'] = $thumbUrl;


      
        unset($responseData['files']);
        return response()->json($responseData);
    }

    public function update(Request $request, Branche $branch)
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
                $branchField = json_decode($request->$field, true);
                foreach ($branchField as $locale => $value) {
                    $branch->setTranslation($field, $locale, $value);
                }
            }

            $branch->status = $request->status;
            $branch->featured = $request->featured;
            $branch->country_id = $request->country_id;
            $branch->governorate_id  = $request->governorate_id;
            $branch->area_id  = $request->area_id;
            $branch->location = $request->location;
            $branch->robots = $request->robots;

            // Persist the branch instance into the database
            $branch->save();

            // Attach files
            $branch->files()->detach();
            if ($request->has('banner') && File::find($request->banner)) {
                $branch->files()->attach($request->banner, ['type' => 'banner']);
            }
            if ($request->has('thumb') && File::find($request->thumb)) {
                $branch->files()->attach($request->thumb, ['type' => 'thumb']);
            }
            $gallery = json_decode($request->gallery, true);
            if (is_array($gallery)) {
                foreach ($gallery as $galleryId) {
                    if (File::find($galleryId)) {
                        $branch->files()->attach($galleryId, ['type' => 'gallery']);
                    }
                }
            }

            // Attach the branch to a healthcare provider
            if ($request->has('healthcare_provider_id')) {
                $healthcareProvider = HealthcareProvider::find($request->healthcare_provider_id);
                if ($healthcareProvider) {
                    $branch->healthcareProviders()->sync([$healthcareProvider->id]);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Healthcare Provider not found.',
                    ], 404);
                }
            }

            // Retrieve banner and thumb URLs
            $branch->load('files');
            $bannerId = null;
            $thumbId = null;
            $bannerUrl = '';
            $thumbUrl = '';

            // Loop through the files to find banner and thumb
            foreach ($branch->files as $file) {
                if ($file->pivot->type == 'banner') {
                    $bannerId = $file->id;  // Store the banner ID
                    $bannerUrl = $file->file_url;
                } elseif ($file->pivot->type == 'thumb') {
                    $thumbId = $file->id;  // Store the thumb ID
                    $thumbUrl = $file->file_url;
                }
            }

            // Prepare response data
            $responseData = $branch->toArray();
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

    public function destroy($id)
    {
        $branche = Branche::find($id);
        if (!$branche) {
            return response()->json(['message' => 'Branche not found'], Response::HTTP_NOT_FOUND);
        }

        $branche->delete(); // Soft delete the country

        return response()->json(['message' => 'Branche soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $branche = Branche::withTrashed()->find($id);
        if (!$branche) {
            return response()->json(['message' => 'Branche not found'], Response::HTTP_NOT_FOUND);
        }

        if ($branche->trashed()) {
            $branche->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Branche permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Branche is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }


    
}
