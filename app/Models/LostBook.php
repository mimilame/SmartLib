<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LostBook extends Model
{
    use HasFactory;
    
    protected $table = 'lost_book';
    protected $primaryKey = 'id'; // Changed from book_id to id based on migration
    
    // Keep timestamps if they're in the migration
    
    protected $fillable = ['book_id', 'isbn', 'member_no', 'date_lost']; // Updated to snake_case and added book_id
    
    // Relationship to Book model
    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id', 'book_id');
    }
    
    // Relationship to Member model
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_no', 'member_id');
    }
}