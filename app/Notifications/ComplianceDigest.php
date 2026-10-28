<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To HR: drivers whose Driver CPC, tacho card or licence photocard has just crossed a
 * reminder threshold (90, 60, 30 or 14 days, or expired).
 */
class ComplianceDigest extends Notification
{
    /** @param list<array{name: string, depot: string, card: string, date: string, days: int, url: string}> $rows */
    public function __construct(public array $rows) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expired = count(array_filter($this->rows, fn (array $r) => $r['days'] < 0));
        $mail = (new MailMessage)
            ->subject('Driver compliance: '.count($this->rows).' card '.(count($this->rows) === 1 ? 'date needs' : 'dates need').' action'.($expired ? " ({$expired} expired)" : ''))
            ->greeting('Driver card and CPC expiries')
            ->line('These dates reached a reminder point today:');
        foreach ($this->rows as $r) {
            $when = match (true) {
                $r['days'] < 0 => 'EXPIRED '.$r['date'],
                $r['days'] === 0 => 'expires TODAY',
                default => 'expires '.$r['date'].' ('.$r['days'].' days)',
            };
            $mail->line("- **{$r['name']}** ({$r['depot']}): {$r['card']} {$when}");
        }

        return $mail->action('Open staff compliance', route('admin.staff.index', ['due' => 1]))
            ->line('A driver must not drive professionally without a valid Driver CPC and, on tachograph work, a valid driver card. Update the new date in the back office once the renewal arrives; reminders for that card then start again.')
            ->salutation('Allinson back office');
    }
}
