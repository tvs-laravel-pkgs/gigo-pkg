<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;

class TvsOneApprovalStatus extends Model
{
    use SeederTrait;
    public $timestamps = false;

    protected $table = 'tvs_one_approval_statuses';
    protected $fillable = [
        "code",
        "name",
    ];

}
