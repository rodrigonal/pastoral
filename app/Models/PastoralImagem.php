<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PastoralImagem extends Model
{
    use HasFactory;

    protected $table = 'pastoral_imagens';

    protected $fillable = [
        'pastoral_id',
        'path',
        'ordem',
    ];

    public function pastoral(): BelongsTo
    {
        return $this->belongsTo(Pastoral::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
