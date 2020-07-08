<?php

namespace Abs\GigoPkg\Mail;
use Abs\BasicPkg\Mail\ConfigurableMail;
use App\WarrantyJobOrderRequest as WarrantyJobOrderRequestModel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class WarrantyJobOrderRequest extends ConfigurableMail {
	use Queueable, SerializesModels;
	public $configs;
	public $wjor;
	public $company;
	public $outlet;
	public $title;
	public $al_warranty_manager;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct($params = []) {
		$this->configs = config('mail.' . WarrantyJobOrderRequestModel::$MAIL_CONFIG);
		$this->wjor = $params['wjor'];
		$this->company = $params['wjor']->company;
		$this->outlet = $params['wjor']->jobOrder->outlet;
		$this->title = 'Warranty Job Order Request';
		$this->al_warranty_manager = $params['al_warranty_manager'];
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build() {
		$this->from($this->configs['from']);

		$this->subject($this->wjor->company->name . " -  Warranty Job Order Request - " . $this->wjor->number);
		return $this->view('email/wjor');

		// $html = View::make('mail.customer.activity-mention.html', [
		//     'subject' => $subject,
		//     'activity' => $this->activity,
		//     'activityUrl' => $activityUrl,
		// ]);

		// $emogrifier = new Emogrifier($html);
		// $html = $emogrifier->emogrify();

		// $message = $this->view('echo')
		//     ->with([
		//         'content' => $html,
		//     ]);

		// return $message;

	}
}
