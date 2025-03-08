<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LostBook extends Model
{
    use HasFactory;
    
    protected $table = 'lost_book';
    protected $primaryKey = 'Book_ID';
    public $timestamps = false;
    
    protected $fillable = ['ISBN', 'Member_No', 'Date_Lost'];
    
    public function member()
    {
        return $this->belongsTo(Member::class, 'Member_No', 'member_id');
    }
}