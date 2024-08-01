<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'email', 'phone', 'date', 'healthcare_provider_id','doctor_id',
        'slot_id', 'lang', 'status', 'age', 'country'
    ];


    public function scheduleDayTime()
    {
        return $this->belongsTo(ScheduleDayTime::class, 'slot_id');
    }
    public function scheduleDayTimeSlot()
    {
        return $this->belongsTo(ScheduleDayTimeSlot::class, 'slot_id');
    }
    public function healthcareProvider()
    {
        return $this->belongsTo(HealthcareProvider::class, 'healthcare_provider_id');
    }
}
