<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    protected $table = 'borrows';
    protected $primaryKey = 'borrow_id';
    public $timestamps = true;
    
    protected $fillable = [
        'member_id',
        'date_borrow',
        'due_date',
    ];
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function borrowDetails()
    {
        return $this->hasMany(BorrowDetails::class, 'borrow_id');
    }
}
