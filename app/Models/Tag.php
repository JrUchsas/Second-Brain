<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Get the notes attached to the tag.
     *
     * @return BelongsToMany<Note, $this>
     */
    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class);
    }
}
