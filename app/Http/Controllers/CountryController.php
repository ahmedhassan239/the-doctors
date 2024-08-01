<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;




class CountryController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['getAllBlogs', 'getSingleBlog','getFeaturedBlogs']]);
    // }
    
    public function index()
    {
        $countries = Country::with('files')->get();
        return response()->json($countries);
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
            $country = new Country();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description',
                'seo_title', 'seo_keywords', 'seo_description',

            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $countryField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($countryField as $locale => $value) {
                    $country->setTranslation($field, $locale, $value);
                }
            }

            // if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            //     $blog->addMediaFromRequest('banner')->toMediaCollection('banner');
            // }

            $country->status = $request->status;
         
            $country->robots = $request->robots;

            // Persist the doctor instance into the database
            $country->save();
            $country->files()->attach($request->flag, ['type' => 'flag']);
            // $blog->files()->attach($request->thumb, ['type' => 'thumb']);

            return response()->json($country);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function show(Country $country)
    {
        // Initialize IDs and URLs
        $flagId = null;
        $flagUrl = '';
     
    
        // Loop through the files to find banner and thumb
        foreach ($country->files as $file) {
            if ($file->pivot->type == 'flag') {
                $flagId = $file->id;  // Store the banner ID
                $flagUrl = $file->file_url;
            }
        }
    
        // Prepare response data
        $responseData = $country->toArray();
        $responseData['flag_id'] = $flagId;
        $responseData['flag_url'] = $flagUrl;
        unset($responseData['files']);
    
        return response()->json($responseData);
    }
    
    public function update(Request $request, Country $country)
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
                    $countryField = json_decode($request->$field, true);
    
                    // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                    //     return response()->json([
                    //         'status' => 'error',
                    //         'message' => 'Validation failed',
                    //         'errors' => ['English ' . $field . ' is required and must be a string'],
                    //     ], 422);
                    // }
    
                    foreach ($countryField as $locale => $value) {
                        $country->setTranslation($field, $locale, $value);
                    }
                }
            }
    
            $country->status = $request->status;
         
            $country->robots = $request->robots;

    
            $country->save();
    
            // Detach existing files and attach new ones
            // if()
            $country->files()->detach();

            $country->files()->attach($request->flag, ['type' => 'flag']);
    
            // Retrieve banner and thumb URLs
            $country->load('files'); 
            // dd($blog->load('files'));// Reload the files relationship
            $flagId = null;
            $flagUrl = '';
        
            // Loop through the files to find banner and thumb
            foreach ($country->files as $file) {
                if ($file->pivot->type == 'flag') {
                    $flagId = $file->id;  // Store the banner ID
                    $flagUrl = $file->file_url;
                }
            }
        
            // Prepare response data
            $responseData = $country->toArray();
            $responseData['flag_id'] = $flagId;
            $responseData['flag_url'] = $flagUrl;

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
        $country = Country::find($id);
        if (!$country) {
            return response()->json(['message' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        $country->delete(); // Soft delete the country

        return response()->json(['message' => 'Country soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $country = Country::withTrashed()->find($id);
        if (!$country) {
            return response()->json(['message' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        if ($country->trashed()) {
            $country->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Country permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Country is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function deletedCountries()
    {
        $deletedCountries = Country::onlyTrashed()->get();
        return response()->json($deletedCountries);
    }

    public function getAllCountries()
    {

        $countries = Country::where('status',1)->get()
            ->map(function ($val){
                $flag = '';
                foreach ($val->files as $file) {
                    if($file->pivot->type == 'flag'){
                        $flag = $file->file_url;
                    }
                }
                return [
                    'id'=>$val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                    'alt'=> $val->name,
                    'flag'=>$flag
                ];
            });

        return response()->json([
            'data'=>$countries
        ]);

    }
}
