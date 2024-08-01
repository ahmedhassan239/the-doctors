<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;


class AreaController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['getAllBlogs', 'getSingleBlog','getFeaturedBlogs']]);
    // }
    
    public function index(Request $request)
    {
        $countryId = $request->header('country_id');
        if (!is_null($countryId)) {

            // Filter specialties by the provided country_id
            $areas = Area::where('country_id', $countryId)->with('files','country','governorate')->get();
        } else {
            // Return all specialties if no country_id is provided
            $areas = Area::with('files','country','governorate')->get();
        }

        return response()->json($areas);
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
            $area = new Area();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description',
                'seo_title', 'seo_keywords', 'seo_description',

            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $areaField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($areaField as $locale => $value) {
                    $area->setTranslation($field, $locale, $value);
                }
            }

            // if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            //     $blog->addMediaFromRequest('banner')->toMediaCollection('banner');
            // }

            $area->country_id = $request->country_id;
            $area->governorate_id = $request->governorate_id;
            $area->status = $request->status;
         
            $area->robots = $request->robots;

            // Persist the doctor instance into the database
            $area->save();
            // $blog->files()->attach($request->thumb, ['type' => 'thumb']);

            return response()->json($area);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function show(Area $area)
    {
        // Prepare response data
        $responseData = $area->toArray();
        return response()->json($responseData);
    }
    
    public function update(Request $request, Area $area)
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
                    $areaField = json_decode($request->$field, true);
    
                    // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                    //     return response()->json([
                    //         'status' => 'error',
                    //         'message' => 'Validation failed',
                    //         'errors' => ['English ' . $field . ' is required and must be a string'],
                    //     ], 422);
                    // }
    
                    foreach ($areaField as $locale => $value) {
                        $area->setTranslation($field, $locale, $value);
                    }
                }
            }
    
            $area->country_id = $request->country_id;
            $area->governorate_id = $request->governorate_id;
            $area->status = $request->status;
            $area->robots = $request->robots;

    
            $area->save();
        
            // Prepare response data
            $responseData = $area->toArray();
    
    
    
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
        $area = Area::find($id);
        if (!$area) {
            return response()->json(['message' => 'Area not found'], Response::HTTP_NOT_FOUND);
        }

        $area->delete(); // Soft delete the country

        return response()->json(['message' => 'Area soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $area = Area::withTrashed()->find($id);
        if (!$area) {
            return response()->json(['message' => 'Area not found'], Response::HTTP_NOT_FOUND);
        }

        if ($area->trashed()) {
            $area->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Area permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Area is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }

    // public function deletedCountries()
    // {
    //     $deletedCountries = Country::onlyTrashed()->get();
    //     return response()->json($deletedCountries);
    // }

    public function getGovernoratesAreas($governorate_id)
    {
        // where('country_id',$country_id)
        $areas = Area::where('governorate_id',$governorate_id)
                        ->where('status',1)
                        // ->with('country','governorate')
                        ->get()
            ->map(function ($val){
    
                return [
                    // 'country'=>[
                    //     'name'=>$val->country->name,
                    //     'slug'=>$val->country->slug,
                    // ],
                    // 'governorate'=>[
                    //     'name'=>$val->governorate->name,
                    //     'slug'=>$val->governorate->slug,
                    // ],
                    'id'=>$val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                   
                ];
            });

        return response()->json([
            'data'=>$areas
        ]);

    }
}
