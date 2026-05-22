<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\TenantInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePdfAttachedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantInvoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        $monthName = \Carbon\Carbon::create(
            $this->invoice->period_year,
            $this->invoice->period_month,
            1
        )->locale('es')->isoFormat('MMMM Y');

        return new Envelope(
            subject: "Comprobante de Factura {$monthName} – {$this->tenant->company_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice-pdf-attached',
        );
    }

    public function attachments(): array
    {
        $absolutePath = storage_path('app/private/' . $this->invoice->pdf_file_path);

        $filename = 'factura_'
            . $this->invoice->period_year . '_'
            . str_pad($this->invoice->period_month, 2, '0', STR_PAD_LEFT)
            . '.pdf';

        return [
            Attachment::fromPath($absolutePath)
                ->as($filename)
                ->withMime('application/pdf'),
        ];
    }
}
