<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartType extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'part_types';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'name',
		'created_by_id',
	];

}