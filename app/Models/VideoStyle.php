<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoStyle extends Model
{
    protected $fillable = ['name', 'description', 'sample_image_url', 'extra_credit_cost'];
}
