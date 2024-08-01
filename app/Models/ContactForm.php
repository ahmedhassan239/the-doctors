<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactForm extends Model
{
    use HasFactory;
    protected $table = 'contacts_form';
    protected $fillable = ['name', 'email','message','status','phone','country'];
    // public function service()
    // {
    //     return $this->belongsTo(Service::class);
    // }
}
