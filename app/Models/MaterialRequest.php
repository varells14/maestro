<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaterialRequest extends Model
{
    use HasFactory;

    protected $table = 'material_request';

    protected $fillable = [
        'request_number',
        'request_name',
        'priority',
        'request_date',
        'notes',
        'status',
        'checker',
        'checker_at',
        'approved',
        'approved_at',
    ];

    protected $dates = [
        'request_date',
        'checker_at',
        'approved_at',
    ];

    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class, 'request_id');
    }
}
