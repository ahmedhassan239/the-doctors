<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;


class GovernorateController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['getAllBlogs', 'getSingleBlog','getFeaturedBlogs']]);
    // }
    
    public function index(Request $request)
    {
        // Attempt to retrieve country_id from request headers
        $countryId = $request->header('country_id');
        if (!is_null($countryId)) {
            // Filter insurances by the provided country_id from the header
            $governorates = Governorate::where('country_id', $countryId)
                                    ->with('country', 'files')->get();
        } else {
            // Return all insurances if no country_id is provided in the header
            $governorates = Governorate::with('country', 'files')->get();
        }
    
        return response()->json($governorates);
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
            $governorate = new Governorate();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description',
                'seo_title', 'seo_keywords', 'seo_description',

            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $governorateField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($governorateField as $locale => $value) {
                    $governorate->setTranslation($field, $locale, $value);
                }
            }

            // if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            //     $blog->addMediaFromRequest('banner')->toMediaCollection('banner');
            // }

            $governorate->country_id = $request->country_id;
            $governorate->status = $request->status;
         
            $governorate->robots = $request->robots;

            // Persist the doctor instance into the database
            $governorate->save();
            // $blog->files()->attach($request->thumb, ['type' => 'thumb']);

            return response()->json($governorate);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function show(Governorate $governorate)
    {
        // Prepare response data
        $responseData = $governorate->toArray();
        return response()->json($responseData);
    }
    
    public function update(Request $request, Governorate $governorate)
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
                'name', 'slug', 'description',
                'seo_title', 'seo_keywords', 'seo_description',
            ];
    
            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $governorateField = json_decode($request->$field, true);
    
                    // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                    //     return response()->json([
                    //         'status' => 'error',
                    //         'message' => 'Validation failed',
                    //         'errors' => ['English ' . $field . ' is required and must be a string'],
                    //     ], 422);
                    // }
    
                    foreach ($governorateField as $locale => $value) {
                        $governorate->setTranslation($field, $locale, $value);
                    }
                }
            }
    
            $governorate->country_id = $request->country_id;
            $governorate->status = $request->status;
            $governorate->robots = $request->robots;

    
            $governorate->save();
        
            // Prepare response data
            $responseData = $governorate->toArray();
    
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
        $governorate = Governorate::find($id);
        if (!$governorate) {
            return response()->json(['message' => 'Governorate not found'], Response::HTTP_NOT_FOUND);
        }

        $governorate->delete(); // Soft delete the country

        return response()->json(['message' => 'Governorate soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $governorate = Governorate::withTrashed()->find($id);
        if (!$governorate) {
            return response()->json(['message' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        if ($governorate->trashed()) {
            $governorate->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Governorate permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Governorate is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }

    // public function deletedCountries()
    // {
    //     $deletedCountries = Country::onlyTrashed()->get();
    //     return response()->json($deletedCountries);
    // }

    public function getAllGovernorates()
    {
        $governorates = Governorate::where('status',1)->with('country')->get()
            ->map(function ($val){
    
                return [
                    'country'=>[
                        'name'=>$val->country->name,
                        'slug'=>$val->country->slug,
                    ],
                    'id'=>$val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                   
                ];
            });

        return response()->json([
            'data'=>$governorates
        ]);

    }

    public function getAllGovernoratesByCountryid($country_id)
    {
        $governorates = Governorate::where('status',1)
                                    ->where('country_id',$country_id)
                                    ->with('country')->get()
            ->map(function ($val){
    
                return [
                    'id'=>$val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                   
                ];
            });

        return response()->json([
            'data'=>$governorates
        ]);

    }
}
