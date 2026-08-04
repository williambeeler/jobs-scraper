<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CareerPathFile extends Model
{
    protected $fillable = ['original_name', 'path', 'mime_type', 'size'];

    protected static function booted(): void
    {
        static::deleted(fn (self $file) => Storage::disk('local')->delete($file->path));
    }

    public function careerPath(): BelongsTo
    {
        return $this->belongsTo(CareerPath::class);
    }

    public function formattedSize(): string
    {
        if ($this->size >= 1_048_576) {
            return number_format($this->size / 1_048_576, 1).' MB';
        }

        return number_format($this->size / 1024, 0).' KB';
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
