<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Area extends Model
{
    use HasFactory,SoftDeletes,HasTranslations;
    
    protected $fillable = [
        'name', 'slug', 'country_id', 'governorate_id', 'description', 
        'seo_title', 'seo_keywords', 'seo_description', 'robots', 'status'
    ];

    public $translatable = [
        'name', 'slug', 'description',
        'seo_title', 'seo_keywords', 'seo_description',
    ];
    protected $dates = ['deleted_at'];

    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }
    public function country()
    {
        return $this->belongsTo(Country::class,'country_id');
    }
    public function governorate()
    {
        return $this->belongsTo(Governorate::class,'governorate_id');
    }
}
