<?php

namespace App\Http\Controllers;

use App\Models\HealthcareProvider;
use App\Models\SubSpecialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class HealthcareProviderController extends Controller
{


    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['getAllBlogs', 'getSingleBlog','getFeaturedBlogs']]);
    // }
    public function index(Request $request)
    {
        // Check if a country_id is provided
        $countryId = $request->header('country_id');
        // dd($countryId);

        if (!is_null($countryId)) {
            // Filter specialties by the provided country_id
            $healthcareProviders = HealthcareProvider::where('country_id', $countryId)
                ->with('country', 'governorate', 'area', 'files')->get();
        } else {
            // Return all specialties if no country_id is provided
            $healthcareProviders = HealthcareProvider::with('country', 'governorate', 'area', 'files')->get();
        }

        return response()->json($healthcareProviders);
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

            // Initialize a new healthcare provider instance
            $healthcareProvider = new HealthcareProvider();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug',
                'description', 'overview', 'seo_title', 'seo_keywords', 'seo_description',
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $healthcareProviderField = json_decode($request->$field, true);

                foreach ($healthcareProviderField as $locale => $value) {
                    $healthcareProvider->setTranslation($field, $locale, $value);
                }
            }

            $healthcareProvider->type = $request->type;
            $healthcareProvider->status = $request->status;
            $healthcareProvider->featured = $request->featured;
            $healthcareProvider->country_id = $request->country_id;
            $healthcareProvider->governorate_id = $request->governorate_id;
            $healthcareProvider->area_id = $request->area_id;
            $healthcareProvider->robots = $request->robots;
            $healthcareProvider->waiting_time = $request->waiting_time;
            $healthcareProvider->fees = $request->fees;
            $healthcareProvider->country_sort = $request->country_sort;
            $healthcareProvider->governorate_sort = $request->governorate_sort;
            $healthcareProvider->area_sort = $request->area_sort;
            $healthcareProvider->specialty_sort = $request->specialty_sort;

            // Persist the healthcare provider instance into the database
            $healthcareProvider->save();

            if ($request->has('specialties')) {
                $specialtyIds = json_decode($request->specialties, true); // Decode the JSON string to PHP array
                if (is_array($specialtyIds)) {
                    $healthcareProvider->specialties()->attach($specialtyIds);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for specialties.',
                    ], 422);
                }
            }

            if ($request->has('insurances')) {
                $insurancesIds = json_decode($request->insurances, true); // Decode the JSON string to PHP array
                if (is_array($insurancesIds)) {
                    $healthcareProvider->insurances()->attach($insurancesIds);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for insurances.',
                    ], 422);
                }
            }

            $healthcareProvider->files()->attach($request->thumb, ['type' => 'thumb']);
            $healthcareProvider->files()->attach($request->banner, ['type' => 'banner']);
            $gallery = json_decode($request->gallery, true);
            if (is_array($gallery)) {
                foreach ($gallery as $galleryId) {
                    $healthcareProvider->files()->attach($galleryId, ['type' => 'gallery']);
                }
            }

            return response()->json($healthcareProvider);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }


    public function show(HealthcareProvider $healthcareProvider)
    {
        // Eager load relationships
        $healthcareProvider->load('doctors', 'insurances', 'specialties', 'branches', 'files');

        // Initialize IDs and URLs for banner and thumb
        $bannerId = null;
        $thumbId = null;
        $bannerUrl = '';
        $thumbUrl = '';

        // Loop through the files to find banner and thumb
        foreach ($healthcareProvider->files as $file) {
            if ($file->pivot->type == 'banner') {
                $bannerId = $file->id;  // Store the banner ID
                $bannerUrl = $file->file_url;  // Assuming you have a file_url attribute
            } elseif ($file->pivot->type == 'thumb') {
                $thumbId = $file->id;  // Store the thumb ID
                $thumbUrl = $file->file_url;  // Assuming you have a file_url attribute
            }
        }

        // Prepare additional related data for response
        $additionalData = [
            'doctors' => $healthcareProvider->doctors->pluck('id'),  // Or any other doctor attributes you need
            'insurances' => $healthcareProvider->insurances->pluck('id'),  // Or any other insurance attributes you need
            'specialties' => $healthcareProvider->specialties->pluck('id'),  // Or any other specialist attributes you need
            'branches' => $healthcareProvider->branches->pluck('id'),  // Or any other branch attributes you need
        ];
        // Prepare response data
        $responseData = $healthcareProvider->toArray();
        $responseData = array_merge($responseData, [
            'banner_id' => $bannerId,
            'thumb_id' => $thumbId,
            'banner_url' => $bannerUrl,
            'thumb_url' => $thumbUrl
        ], $additionalData);

        unset($responseData['files']);  // Remove files from the main response if not needed

        return response()->json($responseData);
    }

    public function update(Request $request, $id)
    {
        try {
            // Find the existing healthcare provider or fail with a 404
            $healthcareProvider = HealthcareProvider::findOrFail($id);

            // Proceed with the same logic as in the store method for setting translations and other properties
            $translatableFields = [
                'name', 'slug',
                'description', 'overview', 'seo_title', 'seo_keywords', 'seo_description',
            ];

            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $healthcareProviderField = json_decode($request->$field, true);
                    foreach ($healthcareProviderField as $locale => $value) {
                        $healthcareProvider->setTranslation($field, $locale, $value);
                    }
                }
            }

            // Update fields if present in the request
            $fieldsToUpdate = [
                'type', 'status', 'featured', 'country_id', 'governorate_id', 'area_id',
                'robots', 'waiting_time', 'fees', 'country_sort', 'governorate_sort',
                'area_sort', 'specialty_sort'
            ];

            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field)) {
                    $healthcareProvider->$field = $request->$field;
                }
            }

            // Save the changes to the healthcare provider
            $healthcareProvider->save();

            // Update relationships
            // if ($request->has('specialties') && is_array($request->specialties)) {
            //     $healthcareProvider->specialties()->sync($request->specialties);
            // }

            // if ($request->has('insurance') && is_array($request->insurance)) {
            //     $healthcareProvider->insurances()->sync($request->insurance);
            // }

            // if ($request->has('doctors') && is_array($request->doctors)) {
            //     $healthcareProvider->doctors()->sync($request->doctors);
            // }

            // if ($request->has('branches') && is_array($request->branches)) {
            //     $healthcareProvider->branches()->sync($request->branches);
            // }

            if ($request->has('specialties')) {
                $specialtyIds = json_decode($request->specialties, true); // Decode the JSON string to PHP array
                if (is_array($specialtyIds)) {
                    $healthcareProvider->specialties()->attach($specialtyIds);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for specialties.',
                    ], 422);
                }
            }

            if ($request->has('doctors')) {
                $doctorIds = json_decode($request->doctors, true); // Decode the JSON string to PHP array
                if (is_array($doctorIds)) {
                    $healthcareProvider->doctors()->attach($doctorIds);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for doctors.',
                    ], 422);
                }
            }
            if ($request->has('insurances')) {
                $insurancesIds = json_decode($request->insurances, true); // Decode the JSON string to PHP array
                if (is_array($insurancesIds)) {
                    $healthcareProvider->insurances()->attach($insurancesIds);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid format for insurances.',
                    ], 422);
                }
            }
            // Assuming files handling remains the same, consider checking for existing attachments or implementing detach logic as needed
            // Example for handling thumb (similar logic can be applied to banner and gallery):
            if ($request->has('thumb')) {
                $healthcareProvider->files()->syncWithoutDetaching([$request->thumb => ['type' => 'thumb']]);
            }

            // Repeat for banner and gallery...

            return response()->json($healthcareProvider);
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
        $healthcareProvider = HealthcareProvider::find($id);
        if (!$healthcareProvider) {
            return response()->json(['message' => 'Healthcare Provider not found'], Response::HTTP_NOT_FOUND);
        }

        $healthcareProvider->delete(); // Soft delete the country

        return response()->json(['message' => 'Healthcare Provider soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $healthcareProvider = HealthcareProvider::withTrashed()->find($id);
        if (!$healthcareProvider) {
            return response()->json(['message' => 'Healthcare Provider not found'], Response::HTTP_NOT_FOUND);
        }

        if ($healthcareProvider->trashed()) {
            $healthcareProvider->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Healthcare Provider permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Healthcare Provider is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }
    public function filter(Request $request)
    {
        $query = HealthcareProvider::query();

        if ($request->filled('search_name')) {
            $searchName = $request->input('search_name');
            $query->where('name', 'like', '%' . $searchName . '%');
        }
        // Filter by direct attributes: country_id, governorate_id, and area_id
        if ($request->filled('country')) {
            $query->where('country_id', $request->input('country'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('governorate')) {
            $query->where('governorate_id', $request->input('governorate'));
        }

        if ($request->filled('area')) {
            $query->where('area_id', $request->input('area'));
        }

        // Filter by specialties (many-to-many relationship)
        if ($request->filled('specialties')) {
            $specialties = $request->input('specialties');
            $query->whereHas('specialties', function ($query) use ($specialties) {
                $query->where('id', $specialties);
            });
        }


        // Filter by insurance (many-to-many relationship)
        if ($request->filled('insurance')) {
            $insurance = $request->input('insurance');
            $query->whereHas('insurances', function ($query) use ($insurance) {
                $query->where('id', $insurance);
            });
        }

        // Execute the query and paginate the results
        $providers = $query->paginate(15);

        // Return the results as a JSON response
        return response()->json($providers);
    }

    // public function singleHealthcareProvider($id)
    // {
    //     $lang = app()->getLocale();

    //     // Try to find by ID or slug
    //     $value = HealthcareProvider::where('status', 1)
    //         ->where(function ($query) use ($id, $lang) {
    //             $query->where('id', $id)->orWhere("slug->$lang", $id);
    //         })
    //         ->with(['files', 'doctors', 'specialties', 'insurances', 'branches'])
    //         ->firstOrFail();

    //     $banner = $value->files->where('pivot.type', 'banner')->first()?->file_url ?? '';
    //     $thumb = $value->files->where('pivot.type', 'thumb')->first()?->file_url ?? '';
    //     // dd($value->doctors);
    //     $doctors = $value->doctors->map(function ($doctor) {
    //         $sub_specialtie = SubSpecialty::where('id', $doctor->sub_specialtie)->get();
    //         return [
    //             'id' => $doctor->id,
    //             'name' => $doctor->name,
    //             'slug' => $doctor->slug,
    //             'overview' => $doctor->overview,
    //             // Assuming subSpecialties is a relationship on the Doctor model
    //             'sub_specialties' => $sub_specialtie,
    //         ];
    //     });
    //     $specialties = $value->specialties->map(function ($q) {
    //         return [
    //             'id' => $q->id,
    //             'name' => $q->name,
    //             'slug' => $q->slug,
    //             'overview' => $q->overview,
    //         ];
    //     });
    //     $insurances = $value->insurances->map(function ($q) {
    //         return [
    //             'id' => $q->id,
    //             'name' => $q->name,
    //             'slug' => $q->slug,
    //             'overview' => $q->overview,
    //         ];
    //     });

    //     $branches = $value->branches->map(function ($q) {
    //         return [
    //             'id' => $q->id,
    //             'name' => $q->name,
    //             'slug' => $q->slug,
    //             'overview' => $q->overview,
    //         ];
    //     });
    //     $data = [
    //         'id' => $value->id,
    //         'name' => $value->name,
    //         'slug' => $value->slug,
    //         'overview' => $value->overview,
    //         'banner' => $banner,
    //         'thumb' => $thumb,
    //         'alt' => $value->name,
    //         'fees' => $value->fees,
    //         'waiting_time' => $value->waiting_time,
    //         'address' => $value->address,
    //         'doctors' => $doctors,
    //         'specialties' => $specialties,
    //         'insurances' => $insurances,
    //         'branches' => $branches,
    //         // Include other necessary fields and relationships
    //         'seo' => [
    //             'title' => $value->seo_title,
    //             'keywords' => $value->seo_keywords,
    //             'description' => $value->seo_description,
    //             'robots' => $value->robots,
    //             'facebook_title' => $value->seo_title,
    //             'facebook_description' => $value->seo_description,
    //             'twitter_title' => $value->seo_title,
    //             'twitter_description' => $value->seo_description,
    //             'twitter_image' => $thumb,
    //             'facebook_image' => $thumb,
    //         ],
    //     ];

    //     return response()->json(['data' => $data], 200);
    // }
    public function singleHealthcareProvider($id)
    {
        $lang = app()->getLocale();

        // Try to find by ID or slug
        $value = HealthcareProvider::where('status', 1)
            ->where(function ($query) use ($id, $lang) {
                $query->where('id', $id)->orWhere("slug->$lang", $id);
            })
            ->with(['files', 'doctors', 'specialties', 'insurances', 'branches'])
            ->firstOrFail();

        $banner = $value->files->where('pivot.type', 'banner')->first()?->file_url ?? '';
        $thumb = $value->files->where('pivot.type', 'thumb')->first()?->file_url ?? '';

        // Process doctors with sub_specialties
        $doctors = $value->doctors->map(function ($doctor) {
            $sub_specialties = SubSpecialty::where('id', $doctor->sub_specialtie)->get()->map(function ($sub_specialty, $lang) {
                return [
                    'id' => $sub_specialty->id,
                    'name' => $sub_specialty->getTranslation('name', $lang),
                    'slug' => $sub_specialty->getTranslation('slug', $lang),
                    'description' => $sub_specialty->getTranslation('description', $lang),
                    'overview' => $sub_specialty->getTranslation('overview', $lang),
                    'seo_title' => $sub_specialty->getTranslation('seo_title', $lang),
                    'seo_keywords' => $sub_specialty->getTranslation('seo_keywords', $lang),
                    'seo_description' => $sub_specialty->getTranslation('seo_description', $lang),
                    'robots' => $sub_specialty->robots,
                    'status' => $sub_specialty->status,
                    'created_at' => $sub_specialty->created_at,
                    'updated_at' => $sub_specialty->updated_at,
                    'deleted_at' => $sub_specialty->deleted_at,
                ];
            });

            return [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'slug' => $doctor->slug,
                'overview' => $doctor->overview,
                'sub_specialties' => $sub_specialties,
            ];
        });

        // Collect additional data
        $specialties = $value->specialties->map(function ($specialty) {
            return [
                'id' => $specialty->id,
                'name' => $specialty->name,
                'slug' => $specialty->slug,
                'overview' => $specialty->overview,
            ];
        });

        $insurances = $value->insurances->map(function ($insurance) {
            return [
                'id' => $insurance->id,
                'name' => $insurance->name,
                'slug' => $insurance->slug,
                'overview' => $insurance->overview,
            ];
        });

        $branches = $value->branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'overview' => $branch->overview,
            ];
        });

        // Prepare the final data structure
        $data = [
            'id' => $value->id,
            'name' => $value->name,
            'slug' => $value->slug,
            'overview' => $value->overview,
            'banner' => $banner,
            'thumb' => $thumb,
            'alt' => $value->name,
            'fees' => $value->fees,
            'waiting_time' => $value->waiting_time,
            'address' => $value->address,
            'doctors' => $doctors,
            'specialties' => $specialties,
            'insurances' => $insurances,
            'branches' => $branches,
            'seo' => [
                'title' => $value->seo_title,
                'keywords' => $value->seo_keywords,
                'description' => $value->seo_description,
                'robots' => $value->robots,
                'facebook_title' => $value->seo_title,
                'facebook_description' => $value->seo_description,
                'twitter_title' => $value->seo_title,
                'twitter_description' => $value->seo_description,
                'twitter_image' => $thumb,
                'facebook_image' => $thumb,
            ],
        ];

        return response()->json(['data' => $data], 200);
    }

    public function getFeaturedHealthcareProviders(Request $request)
    {
        // Fetch featured healthcare providers
        $providers = HealthcareProvider::where('country_id', 1)->where('status', 1)
            ->where('featured', 1) // Assuming you have a 'featured' column to filter featured providers
            ->with('files', 'country', 'governorate', 'area', 'specialties')
            ->get();

        // Format the response
        $data = $providers->map(function ($provider) {
            $thumb = $provider->files->where('pivot.type', 'thumb')->first()?->file_url ?? '';

            // Format specialties
            $specialties = $provider->specialties->map(function ($specialty) {
                return [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'slug' => $specialty->slug,
                ];
            });

            return [
                'governorate' => [
                    'id' => $provider->governorate->id,
                    'name' => $provider->governorate->name,
                    'slug' => $provider->governorate->slug,
                ],
                'area' => [
                    'id' => $provider->area->id,
                    'name' => $provider->area->name,
                    'slug' => $provider->area->slug,
                ],
                'specialties' => $specialties,
                'id' => $provider->id,
                'name' => $provider->name,
                'slug' => $provider->slug,
                'thumb' => $thumb,
                'fees' => $provider->fees,
                'waiting_time' => $provider->waiting_time,
            ];
        });

        return response()->json(['data' => $data], 200);
    }

    public function getBranchesByProviderId($providerId)
    {
        // Find the provider by ID
        $provider = HealthcareProvider::find($providerId);

        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        // Get branches associated with the provider
        $branches = $provider->branches;

        return response()->json($branches, 200);
    }


    public function getSpecialtiesByProviderId($providerId)
    {
        // Find the provider by ID
        $provider = HealthcareProvider::find($providerId);

        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        // Get specialties associated with the provider
        $specialties = $provider->specialties;

        return response()->json($specialties, 200);
    }
}
