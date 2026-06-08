<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    //

    protected $guarded = [];

    //get user's name who uploaded the doc
    public function uploader(){
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    //get dept's name that the doc assign
    public function department(){
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }


}
