<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class GigoInvoice extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'gigo_invoices';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'invoice_number',
		'invoice_date',
		'customer_id',
		'invoice_of_id',
		'entity_id',
		'outlet_id',
		'sbu_id',
		'invoice_amount',
		'received_amount',
		'balance_amount',
		'created_by_id',
		'created_at',
	];
}
