<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    
    protected $table = 'member';
    protected $primaryKey = 'member_id';
    public $timestamps = false;
    
    protected $fillable = [
        'firstname', 
        'lastname', 
        'gender', 
        'address', 
        'contact', 
        'type', 
        'designation', 
        'status',
        'membership_date',
        'borrowed_books_count',
        'expiry_date',
        'email',
        'id_number',
        'department'
    ];
    
    public function borrows()
    {
        return $this->hasMany(Borrow::class, 'member_id');
    }
    // Custom accessor to get full name
    public function getFullNameAttribute()
    {
        return "{$this->firstname} {$this->lastname}";
    }
    
    public function lostBooks()
    {
        return $this->hasMany(LostBook::class, 'Member_No', 'member_id');
    }
    // Define member type relationship
    public function memberType()
    {
        return $this->belongsTo(Type::class, 'type', 'borrowertype');
    }
}
