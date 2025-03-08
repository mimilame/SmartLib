<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;

    protected $table = 'books';
    protected $primaryKey = 'book_id';
    public $timestamps = false;
    
    protected $fillable = [
        'book_title',
        'category_id',
        'author',
        'book_copies',
        'book_pub',
        'publisher_name',
        'isbn',
        'copyright_year',
        'date_receive',
        'date_added',
        'status'
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    public function borrowDetails()
    {
        return $this->hasMany(BorrowDetails::class, 'book_id');
    }
}