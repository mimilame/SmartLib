<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BorrowDetails extends Model
{
    use HasFactory;
    
    protected $table = 'borrowdetails';
    protected $primaryKey = 'borrow_details_id';
    public $timestamps = false;
    
    protected $fillable = [
        'book_id',
        'borrow_id',
        'borrow_status',
        'date_return'
    ];
    
    // Relationship with Book
    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
    
    // Relationship with Borrow
    public function borrow()
    {
        return $this->belongsTo(Borrow::class, 'borrow_id');
    }
}