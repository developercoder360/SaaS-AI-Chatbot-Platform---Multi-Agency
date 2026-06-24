<?php

namespace App\Models;

use App\Models\Traits\HasExternalUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use HasExternalUuid, SoftDeletes, Billable;

    protected $guarded = [];

    protected $casts = [
        'escalation_keywords' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(UsageLog::class);
    }

    public function knowledgeDocuments()
    {
        return $this->hasMany(KnowledgeDocument::class);
    }
}
