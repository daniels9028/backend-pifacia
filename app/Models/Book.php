<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;

class Book extends Model implements Auditable
{
    use HasFactory, SoftDeletes, HasUuids, \OwenIt\Auditing\Auditable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'author',
        'extra_info',
        'is_available'
    ];

    protected $casts = [
        'extra_info' => 'array',
        'is_available' => 'boolean',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function resolveUserId()
    {
        // Use auth()->user() and, as a fallback, request()->user()
        $user = auth()->user() ?? request()->user();
        return $user ? $user->getAuthIdentifier() : null;
    }


    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }
}
