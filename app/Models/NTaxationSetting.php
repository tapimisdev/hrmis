<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NTaxationSetting extends Model
{
    protected $table = 'n_taxation_settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'n_taxation_id',
        'train_law_id',
    ];

    public function taxation(): BelongsTo
    {
        return $this->belongsTo(NTaxation::class, 'n_taxation_id', 'id');
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(NTaxationSettingBonus::class, 'n_taxation_setting_id', 'id');
    }

    public function portion(): HasOne
    {
        return $this->hasOne(NTaxationSettingPortion::class, 'n_taxation_setting_id', 'id');
    }
}
