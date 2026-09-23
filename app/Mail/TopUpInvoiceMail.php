<?php

namespace App\Mail;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TopUpInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $invoiceNumber;

    public string $date;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public float $coins,
        public float $price,
        public string $currency,
        public string $packName,
        public float $newBalance,
        ?string $invoiceNumber = null
    ) {
        $this->invoiceNumber = $invoiceNumber ?: 'INV-NW-'.date('Ymd').'-'.strtoupper(substr(md5((string) microtime()), 0, 6));
        $this->date = date('d M Y, H:i T');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address', 'info@neonwin.co.uk'), config('mail.from.name', 'Neonwin Casino')),
            subject: "Receipt #{$this->invoiceNumber} — Top-Up Successful ({$this->packName})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
        $currencySymbol = $symbols[$this->currency] ?? '€';

        return new Content(
            view: 'emails.topup',
            with: [
                'user' => $this->user,
                'coins' => $this->coins,
                'price' => $this->price,
                'currency' => $this->currency,
                'currencySymbol' => $currencySymbol,
                'packName' => $this->packName,
                'newBalance' => $this->newBalance,
                'invoiceNumber' => $this->invoiceNumber,
                'date' => $this->date,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
        $currencySymbol = $symbols[$this->currency] ?? '€';

        $pdf = Pdf::loadView('invoices.topup_pdf', [
            'user' => $this->user,
            'coins' => $this->coins,
            'price' => $this->price,
            'currency' => $this->currency,
            'currencySymbol' => $currencySymbol,
            'packName' => $this->packName,
            'invoiceNumber' => $this->invoiceNumber,
            'date' => $this->date,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "Invoice-{$this->invoiceNumber}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
