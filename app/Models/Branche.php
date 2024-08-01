<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Branche extends Model
{
    use HasFactory,SoftDeletes,HasTranslations;
    protected $fillable = [
        'name', 'slug','healthcare_provider_id', 'country_id', 'governorate_id', 'city_id',
        'description', 'overview', 'seo_title', 'seo_keywords', 'seo_description', 
        'robots', 'status', 'featured','healthcare_provider_id'
    ];
    protected $dates = ['deleted_at'];

    public $translatable = [
        'name', 'slug', 'description', 'overview',
        'seo_title', 'seo_keywords', 'seo_description',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class,'country_id');
    }
    public function governorate()
    {
        return $this->belongsTo(Governorate::class,'governorate_id');
    }
    public function area()
    {
        return $this->belongsTo(Area::class,'area_id');
    }

    public function specialties()
    {
        return $this->hasMany(Specialty::class);
    }
    // public function insurance()
    // {
    //     return $this->hasMany(Insurance::class);
    // }

    public function healthcareProviders()
    {
        return $this->belongsToMany(HealthCareProvider::class, 'healthcare_provider_branch');
    }

    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }

    
}
