<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\TenantInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyInvoiceNotification extends Mailable
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
            subject: "Factura Mensual {$monthName} – {$this->tenant->company_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.monthly-invoice',
        );
    }
}
