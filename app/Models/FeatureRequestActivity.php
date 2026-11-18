<?php

namespace App\Models;

use App\Enums\FeatureRequestStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureRequestActivity extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'feature_request_id',
        'user_id',
        'from_stage',
        'to_stage',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_stage' => FeatureRequestStage::class,
            'to_stage' => FeatureRequestStage::class,
            'created_at' => 'datetime',
        ];
    }

    public function featureRequest(): BelongsTo
    {
        return $this->belongsTo(FeatureRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
