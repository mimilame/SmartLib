<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    
    protected $table = 'category';
    protected $primaryKey = 'category_id';
    public $timestamps = false;
    
    protected $fillable = ['classname'];
    
    public function books()
    {
        return $this->hasMany(Book::class, 'category_id');
    }
}