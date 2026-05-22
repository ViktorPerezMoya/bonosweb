<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\TenantInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceSuspendedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantInvoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Servicio Suspendido – {$this->tenant->company_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.service-suspended',
        );
    }
}
