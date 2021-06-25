<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Venturecraft\Revisionable\RevisionableTrait;
class PauseWorkReason extends Model {
	use SeederTrait;
	use SoftDeletes;
	use RevisionableTrait;
	protected $table = 'pause_work_reasons';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'name',
	];

	protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;
}
