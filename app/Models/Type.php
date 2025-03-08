<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;
    
    protected $table = 'type';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = ['borrowertype'];
    
    public function members()
    {
        return $this->hasMany(Member::class, 'type', 'borrowertype');
    }
}