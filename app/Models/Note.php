<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    protected $fillable = ['body'];

    /**
     * @return MorphTo<Model, $this>
     */
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
