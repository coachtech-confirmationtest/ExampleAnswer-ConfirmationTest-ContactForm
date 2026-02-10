<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * このタグが紐付けられている全てのお問い合わせを取得
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class)->withTimestamps();
    }
}