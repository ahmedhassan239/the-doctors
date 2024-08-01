<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;


class BlogController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['getAllBlogs', 'getSingleBlog','getFeaturedBlogs']]);
    // }
    
    public function index()
    {
        $blogs = Blog::all();
        return response()->json($blogs);
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
            $blog = new Blog();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description', 'overview',
                'seo_title', 'seo_keywords', 'seo_description',

            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $blogField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($blogField as $locale => $value) {
                    $blog->setTranslation($field, $locale, $value);
                }
            }

            if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
                $blog->addMediaFromRequest('banner')->toMediaCollection('banner');
            }

            $blog->status = $request->status;
            $blog->featured = $request->featured;
            $blog->robots = $request->robots;
            $blog->related_blogs = $request->related_blogs;
            $blog->writer = $request->writer;
            $blog->category_id = $request->category_id;

            // Persist the doctor instance into the database
            $blog->save();
            $blog->files()->attach($request->banner, ['type' => 'banner']);
            $blog->files()->attach($request->thumb, ['type' => 'thumb']);

            return response()->json($blog);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function show(Blog $blog)
    {
        // Initialize IDs and URLs
        $bannerId = null;
        $thumbId = null;
        $bannerUrl = '';
        $thumbUrl = '';
    
        // Loop through the files to find banner and thumb
        foreach ($blog->files as $file) {
            if ($file->pivot->type == 'banner') {
                $bannerId = $file->id;  // Store the banner ID
                $bannerUrl = $file->file_url;
            } elseif ($file->pivot->type == 'thumb') {
                $thumbId = $file->id;  // Store the thumb ID
                $thumbUrl = $file->file_url;
            }
        }
    
        // Prepare response data
        $responseData = $blog->toArray();
        $responseData['banner_id'] = $bannerId;
        $responseData['thumb_id'] = $thumbId;
        $responseData['banner_url'] = $bannerUrl;
        $responseData['thumb_url'] = $thumbUrl;
        unset($responseData['files']);
    
        return response()->json($responseData);
    }
    

    
    public function update(Request $request, Blog $blog)
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
                'name', 'slug', 'description', 'overview',
                'seo_title', 'seo_keywords', 'seo_description',
            ];
    
            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $blogField = json_decode($request->$field, true);
    
                    // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                    //     return response()->json([
                    //         'status' => 'error',
                    //         'message' => 'Validation failed',
                    //         'errors' => ['English ' . $field . ' is required and must be a string'],
                    //     ], 422);
                    // }
    
                    foreach ($blogField as $locale => $value) {
                        $blog->setTranslation($field, $locale, $value);
                    }
                }
            }
    
            $blog->status = $request->status;
            $blog->featured = $request->featured;
            $blog->robots = $request->robots;
            $blog->related_blogs = $request->related_blogs;
            $blog->writer = $request->writer;
            $blog->category_id = $request->category_id;

    
            $blog->save();
    
            // Detach existing files and attach new ones
            // if()
            $blog->files()->detach();

            $blog->files()->attach($request->banner, ['type' => 'banner']);
            $blog->files()->attach($request->thumb, ['type' => 'thumb']);
    
            // Retrieve banner and thumb URLs
            $blog->load('files'); 
            // dd($blog->load('files'));// Reload the files relationship
            $bannerId = null;
            $thumbId = null;
            $bannerUrl = '';
            $thumbUrl = '';
        
            // Loop through the files to find banner and thumb
            foreach ($blog->files as $file) {
                if ($file->pivot->type == 'banner') {
                    $bannerId = $file->id;  // Store the banner ID
                    $bannerUrl = $file->file_url;
                } elseif ($file->pivot->type == 'thumb') {
                    $thumbId = $file->id;  // Store the thumb ID
                    $thumbUrl = $file->file_url;
                }
            }
        
            // Prepare response data
            $responseData = $blog->toArray();
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
    
    public function destroy(Blog $blog)
    {
        $blog->delete();

        // Return a success message in JSON format
        return response()->json(['message' => 'Blog successfully deleted'], 200);
    }

    public function getAllBlogs()
    {
        // app()->setLocale($lang);

        $blogs = Blog::where('status',1)->get()
            ->map(function ($val){
                $thumb = '';
                foreach ($val->files as $file) {
                    if($file->pivot->type == 'thumb'){
                        $thumb = $file->file_url;
                    }
                }
                return [
                    'id'=>$val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                    'description' => $val->description ?? [],
                    'alt'=> $val->name,
                    'thumb'=>$thumb,
                    'created_at' => $val->created_at->isoFormat('MMM Do YY'),
                ];
            });

        return response()->json([
            'data'=>$blogs
        ]);

    }

    public function getFeaturedBlogs()
    {
        // $lang = app()->getLocale();

        $blogs = Blog::where('status',1)->where('featured',1)->get()
            ->map(function ($val){
                $thumb = '';
                foreach ($val->files as $file) {
                    if($file->pivot->type == 'thumb'){
                        $thumb = $file->file_url;
                    }
                }
                return [
                    'id'=>$val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                    'description' => $val->description ?? [],
                    'alt'=> $val->name,
                    'thumb'=>$thumb,
                    'created_at' => $val->created_at->isoFormat('MMM Do YY'),

                ];
            });

        return response()->json([
            'data'=>$blogs
        ]);

    }

    public function getSingleBlog($id)
    {
        // app()->setLocale($lang);
        $lang = app()->getLocale();

    // Try to find by ID first
    $value = Blog::where('status', 1)->where('id', $id)->with('files')->first();

    // If not found, try to find by slug
    if (!$value) {
        $value = Blog::where('status', 1)
                     ->where("slug->$lang", $id)
                     ->with('files','category')
                     ->firstOrFail();
    }

        $banner = '';
        foreach ($value->files as $file) {
            if($file->pivot->type == 'banner'){
                $banner = $file->file_url;
            }
        }

        $thumb = '';
        foreach ($value->files as $file) {
            if($file->pivot->type == 'thumb'){
                $thumb = $file->file_url;
            }
        }
        $data[] = [
            'id' => $value->id,
            'name' => $value->name,
            'slug' => $value->slug,
            'overview' => $value->overview,
            'banner' => $banner,
            'alt' => $value->name,
            'related_blogs'=> $value->related_blogs_list ?? [],
            'created_at' => $value->created_at->isoFormat('MMM Do YY'),
            'category'=>[
               'id'=>$value->category->id,
               'name'=>$value->category->name
            ],
            'writer'=>$value->writer,
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
        return response()->json([
            'data' => $data,
        ], '200');
    }
}
