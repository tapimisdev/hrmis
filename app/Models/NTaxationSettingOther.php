<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NTaxationSettingOther extends Model
{
    protected $table = 'n_taxation_setting_others';

    protected $primaryKey = 'UniqueID';

    public $timestamps = false;

    protected $fillable = [
        'n_taxation_setting_id',
        'name',
        'amount',
        'is_taxable',
        'is_exempt_bir',
    ];

    public function taxationSetting(): BelongsTo
    {
        return $this->belongsTo(NTaxationSetting::class, 'n_taxation_setting_id', 'UniqueID');
    }
}
