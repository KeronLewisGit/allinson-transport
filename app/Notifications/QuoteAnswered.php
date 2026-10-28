<?php

namespace App\Notifications;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To traffic: the customer accepted or declined a quote through their link. */
class QuoteAnswered extends Notification
{
    public function __construct(public Quote $quote) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $q = $this->quote;
        $accepted = $q->status === QuoteStatus::Accepted;
        $mail = (new MailMessage)
            ->subject(($accepted ? 'Accepted: ' : 'Declined: ')."{$q->reference} {$q->route()} ".gbp($q->price))
            ->greeting(($accepted ? 'Quote accepted' : 'Quote declined').", {$q->reference}")
            ->line('**Customer:** '.trim(($q->company ? $q->company.', ' : '').$q->contact_name).' · '.format_phone($q->phone))
            ->line("**Collection:** {$q->collection_date->format('l j F')} from {$q->collection_postcode}")
            ->line('**Depot:** '.$q->depotName());
        if ($q->customer_note) {
            $mail->line('**Their note:** '.$q->customer_note);
        }

        return $mail->action('Open the request', $q->adminUrl());
    }
}
