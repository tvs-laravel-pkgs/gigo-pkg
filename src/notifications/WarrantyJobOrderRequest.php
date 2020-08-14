<?php

namespace Abs\GigoPkg\Notifications;

use Abs\GigoPkg\Mail\WarrantyJobOrderRequest as Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WarrantyJobOrderRequest extends Notification {
	use Queueable;

	public $wjor;

	/**
	 * Create a new notification instance.
	 *
	 * @return void
	 */
	public function __construct($params = []) {
		$this->wjor = $params['wjor'];
	}

	/**
	 * Get the notification's delivery channels.
	 *
	 * @param  mixed  $notifiable
	 * @return array
	 */
	public function via($notifiable) {
		return ['mail'];
	}

	/**
	 * Get the mail representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return \Illuminate\Notifications\Messages\MailMessage
	 */
	public function toMail($al_warranty_manager) {
		return (new Mailable([
			'al_warranty_manager' => $al_warranty_manager,
			'wjor' => $this->wjor,
		]))
			->to($al_warranty_manager->email)
			->cc($this->wjor->cc_emails);
	}

	/**
	 * Get the array representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return array
	 */
	public function toArray($notifiable) {
		return [
			//
		];
	}
}
