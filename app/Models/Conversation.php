<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasExternalUuid;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasExternalUuid, BelongsToTenant;

    protected $guarded = [];
    
    protected $casts = [
        'needs_human' => 'boolean',
    ];
}
