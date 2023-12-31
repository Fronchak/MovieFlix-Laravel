<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function rules() {
        return [
            'name' => 'required|min:3|max:50|unique:genres,name,' . $this->id,
            'image' => 'required|file|mimes:jpeg,jpg,png'
        ];
    }

    public function feedback() {
        return [
            'required' => 'The :attribute is required',
            'unique' => 'Genre name already used',
            'image' => 'Invalid image file type'
        ];
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class);
    }
}
