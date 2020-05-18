<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PauseWorkReason extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'pause_work_reasons';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'name',
	];
}
