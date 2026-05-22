<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Factura – BonosWeb</title>
</head>
<body style="margin:0; padding:0; background-color:#0f172a; font-family: 'Segoe UI', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #064e3b 0%, #065f46 100%); border-radius: 16px 16px 0 0; padding: 36px 40px; text-align:center;">
                            <p style="margin:0 0 8px 0; font-size:13px; color:#6ee7b7; letter-spacing:2px; text-transform:uppercase; font-weight:600;">BonosWeb</p>
                            <h1 style="margin:0; font-size:26px; font-weight:700; color:#ffffff;">Factura Disponible</h1>
                            <p style="margin:8px 0 0 0; font-size:16px; color:#a7f3d0;">
                                @php
                                    $monthName = \Carbon\Carbon::create($invoice->period_year, $invoice->period_month, 1)
                                        ->locale('es')->isoFormat('MMMM [de] Y');
                                @endphp
                                {{ ucfirst($monthName) }}
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color:#1e293b; padding: 36px 40px;">

                            <p style="margin:0 0 24px 0; font-size:16px; color:#cbd5e1; line-height:1.6;">
                                Estimado administrador de <strong style="color:#f1f5f9;">{{ $tenant->company_name }}</strong>,
                            </p>
                            <p style="margin:0 0 32px 0; font-size:15px; color:#94a3b8; line-height:1.6;">
                                Le informamos que el comprobante de factura correspondiente al período
                                <strong style="color:#f1f5f9;">{{ ucfirst($monthName) }}</strong> ya se encuentra disponible.
                                El documento <strong style="color:#34d399;">PDF se adjunta a este correo</strong> para su archivo y registro contable.
                            </p>

                            {{-- Invoice detail card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a; border-radius:12px; border:1px solid #334155; margin-bottom:28px;">
                                <tr>
                                    <td style="padding:24px 28px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b;">
                                                    <span style="color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Empresa</span>
                                                </td>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b; text-align:right;">
                                                    <span style="color:#f1f5f9; font-size:15px; font-weight:600;">{{ $tenant->company_name }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b;">
                                                    <span style="color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Período</span>
                                                </td>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b; text-align:right;">
                                                    <span style="color:#f1f5f9; font-size:15px;">{{ ucfirst($monthName) }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b;">
                                                    <span style="color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Monto</span>
                                                </td>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b; text-align:right;">
                                                    <span style="color:#34d399; font-size:18px; font-weight:700;">$ {{ number_format($invoice->amount, 2, ',', '.') }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b;">
                                                    <span style="color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Vencimiento</span>
                                                </td>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b; text-align:right;">
                                                    <span style="color:#fbbf24; font-size:15px; font-weight:600;">
                                                        {{ \Carbon\Carbon::parse($invoice->due_date)->locale('es')->isoFormat('dddd D [de] MMMM') }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 14px 0 4px 0;">
                                                    <span style="color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Estado</span>
                                                </td>
                                                <td style="padding: 14px 0 4px 0; text-align:right;">
                                                    @if($invoice->status === 'paid')
                                                        <span style="color:#34d399; font-size:15px; font-weight:700;">✓ Pagada</span>
                                                    @elseif($invoice->status === 'overdue')
                                                        <span style="color:#f87171; font-size:15px; font-weight:700;">⚠ Vencida</span>
                                                    @else
                                                        <span style="color:#fbbf24; font-size:15px; font-weight:700;">Pendiente de pago</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- PDF notice --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#052e16; border-radius:10px; border:1px solid #166534; margin-bottom:28px;">
                                <tr>
                                    <td style="padding:18px 24px; display:flex; align-items:center; gap:12px;">
                                        <p style="margin:0; font-size:14px; color:#86efac; line-height:1.6;">
                                            📎 <strong>El comprobante en PDF se adjunta a este correo.</strong>
                                            También puede descargarlo en cualquier momento desde la sección
                                            <em>Historial de Facturas</em> de su panel de BonosWeb.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:13px; color:#64748b; line-height:1.6;">
                                Si tiene alguna consulta sobre esta factura, por favor contáctenos respondiendo este correo.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#0f172a; border-radius: 0 0 16px 16px; padding: 20px 40px; text-align:center; border-top:1px solid #1e293b;">
                            <p style="margin:0; font-size:12px; color:#475569;">
                                Este es un correo automático generado por <strong style="color:#60a5fa;">BonosWeb</strong>. Por favor no responda directamente a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
