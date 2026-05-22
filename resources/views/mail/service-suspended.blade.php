<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio Suspendido – BonosWeb</title>
</head>
<body style="margin:0; padding:0; background-color:#0f172a; font-family: 'Segoe UI', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); border-radius: 16px 16px 0 0; padding: 36px 40px; text-align:center;">
                            <p style="margin:0 0 8px 0; font-size:13px; color:#fca5a5; letter-spacing:2px; text-transform:uppercase; font-weight:600;">BonosWeb</p>
                            <h1 style="margin:0; font-size:26px; font-weight:700; color:#ffffff;">⚠️ Servicio Suspendido</h1>
                            <p style="margin:8px 0 0 0; font-size:15px; color:#fecaca;">
                                Acción requerida de forma urgente
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color:#1e293b; padding: 36px 40px;">

                            <p style="margin:0 0 24px 0; font-size:16px; color:#cbd5e1; line-height:1.6;">
                                Estimado administrador de <strong style="color:#f1f5f9;">{{ $tenant->company_name }}</strong>,
                            </p>
                            <p style="margin:0 0 28px 0; font-size:15px; color:#94a3b8; line-height:1.6;">
                                Lamentamos informarle que el acceso a <strong style="color:#f1f5f9;">BonosWeb</strong> para su empresa ha sido <strong style="color:#f87171;">suspendido automáticamente</strong> debido a que existe una deuda vencida que supera los <strong style="color:#f1f5f9;">14 días</strong> sin registrar un pago aprobado.
                            </p>

                            {{-- Suspension detail card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a; border-radius:12px; border:1px solid #7f1d1d; margin-bottom:28px;">
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
                                                    <span style="color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Período adeudado</span>
                                                </td>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b; text-align:right;">
                                                    @php
                                                        $period = \Carbon\Carbon::create($invoice->period_year, $invoice->period_month, 1)
                                                            ->locale('es')->isoFormat('MMMM [de] Y');
                                                    @endphp
                                                    <span style="color:#f1f5f9; font-size:15px;">{{ ucfirst($period) }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b;">
                                                    <span style="color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Fecha de Vencimiento</span>
                                                </td>
                                                <td style="padding: 10px 0; border-bottom:1px solid #1e293b; text-align:right;">
                                                    <span style="color:#fbbf24; font-size:15px; font-weight:600;">
                                                        {{ \Carbon\Carbon::parse($invoice->due_date)->locale('es')->isoFormat('D [de] MMMM [de] Y') }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 14px 0 4px 0;">
                                                    <span style="color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Saldo Total Adeudado</span>
                                                </td>
                                                <td style="padding: 14px 0 4px 0; text-align:right;">
                                                    <span style="color:#f87171; font-size:22px; font-weight:700;">$ {{ number_format($tenant->current_balance, 2, ',', '.') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Reactivation steps --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#1e3a5f; border-radius:12px; border:1px solid #1e40af; margin-bottom:28px;">
                                <tr>
                                    <td style="padding:20px 28px;">
                                        <p style="margin:0 0 12px 0; font-size:14px; font-weight:700; color:#93c5fd; text-transform:uppercase; letter-spacing:1px;">¿Cómo reactivar su servicio?</p>
                                        <p style="margin:0 0 8px 0; font-size:14px; color:#bfdbfe; line-height:1.6;">
                                            <strong style="color:#f1f5f9;">1.</strong> Realice el pago del saldo adeudado.
                                        </p>
                                        <p style="margin:0 0 8px 0; font-size:14px; color:#bfdbfe; line-height:1.6;">
                                            <strong style="color:#f1f5f9;">2.</strong> Contacte a soporte de BonosWeb adjuntando el comprobante.
                                        </p>
                                        <p style="margin:0; font-size:14px; color:#bfdbfe; line-height:1.6;">
                                            <strong style="color:#f1f5f9;">3.</strong> Un administrador verificará el pago y reactivará su acceso manualmente.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:13px; color:#64748b; line-height:1.6;">
                                Lamentamos los inconvenientes que esto pueda ocasionar. Si cree que esto es un error o ya ha realizado el pago, por favor contáctenos de inmediato respondiendo este correo.
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
