<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Loan;

class LoanStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public $loan;
    public $status;
    public $message;

    /**
     * Create a new message instance.
     */
    public function __construct(Loan $loan, string $status, string $message)
    {
        $this->loan = $loan;
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->status) {
            'approved' => '✅ Félicitations : Votre demande de prêt a été approuvée !',
            'rejected' => 'ℹ️ Information concernant votre demande de prêt',
            default => 'Mise à jour concernant votre prêt'
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.loans.status_changed',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
