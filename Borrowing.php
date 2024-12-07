<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'borrowed_by',
        'lended_by',
        'borrowed_at',
        'due_at',
        'returned_at',
        'status'
    ];

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrowed_by');
    }

    public function fine()
    {
        return $this->hasOne(Fine::class, 'borrowing_id');
    }
}
