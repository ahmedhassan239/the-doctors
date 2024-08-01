<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Country extends Model
{
    use HasFactory,SoftDeletes,HasTranslations;
    protected $fillable = [
        'name', 'slug', 'description', 'seo_title', 
        'seo_keywords', 'seo_description', 'robots', 'status'
    ];
    protected $dates = ['deleted_at'];

    public $translatable = [
        'name', 'slug', 'description',
        'seo_title', 'seo_keywords', 'seo_description',
    ];

    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }
    
    public function governorates()
    {
        return $this->hasMany(Governorate::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }
}
