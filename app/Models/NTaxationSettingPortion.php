<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NTaxationSettingPortion extends Model
{
    protected $table = 'n_taxation_setting_portion';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'n_taxation_setting_id',
        'hazard_pay',
        'salary',
        'longevity',
    ];

    public function taxationSetting(): BelongsTo
    {
        return $this->belongsTo(NTaxationSetting::class, 'n_taxation_setting_id', 'id');
    }
}
