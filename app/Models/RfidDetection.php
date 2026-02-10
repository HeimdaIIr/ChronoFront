<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidDetection extends Model
{
    /**
     * The connection name for the model.
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'reader_id',
        'serial',
        'raw_time',
        'entrant_id',
        'wave_id',
        'processed',
        'created_result_id',
        'action_taken',
        'error_message',
    ];

    protected $casts = [
        'raw_time' => 'datetime',
        'processed' => 'boolean',
    ];

    public function reader()
    {
        return $this->belongsTo(Reader::class);
    }

    public function entrant()
    {
        return $this->belongsTo(Entrant::class);
    }

    public function wave()
    {
        return $this->belongsTo(Wave::class);
    }

    public function result()
    {
        return $this->belongsTo(Result::class, 'created_result_id');
    }
}
