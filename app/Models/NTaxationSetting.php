<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NTaxationSetting extends Model
{
    protected $table = 'n_taxation_settings';

    protected $primaryKey = 'UniqueID';

    public $timestamps = false;

    protected $fillable = [
        'n_taxation_id',
        'train_law_id',
        'is_longevity',
        'is_hazard_pay',
        'is_less_bir',
    ];

    public function taxation(): BelongsTo
    {
        return $this->belongsTo(NTaxation::class, 'n_taxation_id', 'UniqueID');
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(NTaxationSettingBonus::class, 'n_taxation_setting_id', 'UniqueID');
    }

    public function others(): HasMany
    {
        return $this->hasMany(NTaxationSettingOther::class, 'n_taxation_setting_id', 'UniqueID');
    }

    public function portion(): HasOne
    {
        return $this->hasOne(NTaxationSettingPortion::class, 'n_taxation_setting_id', 'UniqueID');
    }
}
