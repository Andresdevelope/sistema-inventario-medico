<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de recuperación</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    @php
        $logoCid = null;
        $logoPath = public_path('logouptag.png');
        if (isset($message) && is_object($message) && file_exists($logoPath)) {
            try {
                $logoCid = $message->embed($logoPath);
            } catch (\Throwable $e) {
                $logoCid = null;
            }
        }
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#ff8c00,#ffb347);padding:22px 24px;text-align:center;">
                            <img src="{{ $logoCid ?: $logoUrl }}" alt="Logo" width="56" height="56" style="display:block;margin:0 auto 10px auto;border-radius:10px;background:#fff;object-fit:cover;">
                            <h1 style="margin:0;font-size:22px;line-height:1.2;color:#ffffff;">Sistema de Inventario Médico</h1>
                            <p style="margin:8px 0 0 0;color:#fff7ed;font-size:13px;">Verificación para recuperación de contraseña</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px 0;font-size:15px;">Hola <strong>{{ $userName }}</strong>,</p>
                            <p style="margin:0 0 18px 0;font-size:14px;color:#4b5563;">Recibimos una solicitud para recuperar tu contraseña. Usa este código de verificación:</p>

                            <div style="margin:0 0 18px 0;padding:16px;border:1px dashed #f59e0b;background:#fff7ed;border-radius:12px;text-align:center;">
                                <span style="display:inline-block;font-size:34px;letter-spacing:8px;font-weight:800;color:#b45309;">{{ $code }}</span>
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px 0;">
                                <tr>
                                    <td style="font-size:13px;color:#6b7280;padding:8px 0;">⏱️ Vigencia del código:</td>
                                    <td align="right" style="font-size:13px;color:#111827;font-weight:700;padding:8px 0;">{{ $minutes }} minutos</td>
                                </tr>
                                <tr>
                                    <td style="font-size:13px;color:#6b7280;padding:8px 0;">🔐 Uso:</td>
                                    <td align="right" style="font-size:13px;color:#111827;font-weight:700;padding:8px 0;">Un solo intento válido</td>
                                </tr>
                            </table>

                            <div style="margin-top:10px;padding:12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;">
                                <p style="margin:0;font-size:12px;color:#6b7280;line-height:1.5;">
                                    Si no solicitaste este cambio, puedes ignorar este mensaje. Tu contraseña actual seguirá siendo válida.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 24px;background:#fafafa;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;">
                                Este correo fue generado automáticamente por {{ $appName }}.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
