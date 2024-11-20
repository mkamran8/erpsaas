<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\Setting\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Vendor extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'vendors';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'contractor_type',
        'ssn',
        'currency_code',
        'account_number',
        'website',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function contact(): MorphOne
    {
        return $this->morphOne(Contact::class, 'contactable');
    }
}
