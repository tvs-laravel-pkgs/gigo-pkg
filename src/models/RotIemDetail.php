<?php

namespace Abs\GigoPkg;

use App\{Company, Business};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RotIemDetail extends Model
{
    use SoftDeletes;
    protected $table = 'rot_iem_details';
    protected $fillable = [
    	'company_id',
        'business_id',
        'rot_code',
        'job_group',
        'name',
        'km',
        'man_days',
        'working_hrs_start_time',
        'working_hrs_close_time',
        'total_working_hrs',
        'onsite_price',
        'rehab_price',
        'remarks',
        'created_by_id ',
    ];
    protected $appends = ['code_and_name'];

    public function company(){
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function business(){
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function getCodeAndNameAttribute() {
        return $this->rot_code . ' / ' . $this->name;
    }
}
