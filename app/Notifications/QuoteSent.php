<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the customer: the price for their request, with a signed link to accept or decline it. */
class QuoteSent extends Notification
{
    public function __construct(public Quote $quote, public string $url) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $q = $this->quote;
        $mail = (new MailMessage)
            ->subject("Your haulage quote {$q->reference}: ".gbp($q->price).' + VAT')
            ->greeting('Hello '.$q->contact_name.',')
            ->line("Thank you for your request. Here is our price for {$q->service->label()}, {$q->route()}, collecting ".$q->collection_date->format('l j F').'.')
            ->line('**'.gbp($q->price).' plus VAT**'.($q->valid_until ? ', valid until '.$q->valid_until->format('j F Y') : '').'.');
        if ($q->price_notes) {
            $mail->line($q->price_notes);
        }

        return $mail->action('View and accept the quote', $this->url)
            ->line('The link needs no password. To book, accept it there or reply to this email; our '.$q->depotName().' traffic office will confirm the collection.')
            ->salutation('Traffic office, George Allinson (Transport) Ltd · '.ga('phone'));
    }
}
