<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NTaxation extends Model
{
    protected $table = 'n_taxation';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'Year',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(NTaxationSetting::class, 'n_taxation_id', 'id');
    }
}
