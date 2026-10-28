<?php

namespace App\Notifications;

use App\Enums\ApplicantDocumentKind;
use App\Models\JobApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To an applicant: a signed link to upload copies of their licence, cards and right-to-work document. */
class DocumentsRequested extends Notification
{
    public function __construct(public JobApplication $application, public string $url) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->application;
        $mail = (new MailMessage)
            ->subject("Next step for your application {$a->reference}")
            ->greeting('Hello '.$a->first_name.',')
            ->line('Thank you for applying for '.$a->position().'. Before we go further, please send us photos or scans of:');
        foreach (ApplicantDocumentKind::forDriving($a->drives()) as $kind) {
            $mail->line('- '.$kind->label());
        }
        if ($a->assessment_at) {
            $mail->line('Your assessment drive is booked for '.$a->assessment_at->format('l j F \a\t g:i a').' at our '.$a->depotName().' depot. Please bring the original cards with you.');
        }

        return $mail->action('Upload your documents', $this->url)
            ->line('The link needs no password and works for '.JobApplication::DOCUMENTS_LINK_DAYS.' days. Your copies are stored privately and seen only by our HR team.')
            ->salutation('HR, George Allinson (Transport) Ltd · '.ga('phone'));
    }
}
