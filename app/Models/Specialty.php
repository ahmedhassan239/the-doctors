<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Specialty extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'name', 'slug', 'description', 'overview', 'country_id','featured',
        'seo_title', 'seo_keywords', 'seo_description', 'robots', 'status'
    ];

    protected $dates = ['deleted_at'];

    public $translatable = [
        'name', 'slug', 'description', 'overview',
        'seo_title', 'seo_keywords', 'seo_description',
    ];

    public function sub_specialties()
    {
        return $this->hasMany(SubSpecialty::class);
    }

    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }


    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }


    public function healthcareProviders()
    {
        return $this->belongsToMany(HealthCareProvider::class, 'healthcare_provider_specialty');
    }

}
