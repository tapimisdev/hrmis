<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NTaxationSettingBonus extends Model
{
    protected $table = 'n_taxation_setting_bonuses';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'n_taxation_setting_id',
        'government_bonus_id',
    ];

    public function taxationSetting(): BelongsTo
    {
        return $this->belongsTo(NTaxationSetting::class, 'n_taxation_setting_id', 'id');
    }
}
