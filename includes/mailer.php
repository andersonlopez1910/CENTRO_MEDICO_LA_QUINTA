<?php
declare(strict_types=1);

/** @param array<string, string> $values
 *  @return array{subject:string,html:string,text:string}
 */
function pqrsf_notification_message(string $reference, array $values): array
{
    $labels = pqrsf_types();
    $documentLabels = pqrsf_document_types();
    $type = $labels[$values['request_type']] ?? 'PQRSF';
    $row = static fn (string $label, string $value): string => '<tr><th style="padding:8px;text-align:left;border-bottom:1px solid #dde5ea">' . e($label) . '</th><td style="padding:8px;border-bottom:1px solid #dde5ea">' . nl2br(e($value)) . '</td></tr>';
    return [
        'subject' => sprintf('[NUEVA PQRSF Radicado #%s] - %s - %s', $reference, $type, $values['full_name']),
        'html' => '<div style="font-family:Arial,sans-serif;color:#172c43"><h2>Nueva PQRSF</h2><p>Se registró una solicitud con el siguiente detalle:</p><table style="border-collapse:collapse;width:100%;max-width:720px">'
            . $row('Radicado', $reference)
            . $row('Tipo de solicitud', $type)
            . $row('Nombre completo', $values['full_name'])
            . $row('Documento', ($documentLabels[$values['document_type']] ?? $values['document_type']) . ' · ' . $values['document_number'])
            . $row('Correo electrónico', $values['email'])
            . $row('Teléfono', $values['phone'])
            . $row('Asunto', $values['subject'])
            . $row('Detalle', $values['message'])
            . $row('Política de datos', 'Aceptada')
            . '</table></div>',
        'text' => "Nueva PQRSF\nRadicado: {$reference}\nTipo: {$type}\nNombre: {$values['full_name']}\nDocumento: {$values['document_number']}\nCorreo: {$values['email']}\nTeléfono: {$values['phone']}\nAsunto: {$values['subject']}\nDetalle: {$values['message']}\nPolítica de datos: Aceptada",
    ];
}

/** @param array<string, string> $values */
function send_pqrsf_smtp_notification(string $reference, array $values): bool
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('PHPMailer no está instalado; no se envió la notificación PQRSF ' . $reference);
        return false;
    }
    require_once $autoload;

    $username = trim((string) getenv('SMTP_USERNAME'));
    $password = (string) getenv('SMTP_PASSWORD');
    if ($username === '' || $password === '') {
        error_log('Faltan las credenciales SMTP para la PQRSF ' . $reference);
        return false;
    }

    $message = pqrsf_notification_message($reference, $values);
    $recipient = getenv('PQRSF_NOTIFICATION_EMAIL') ?: 'direccionadm.cmq@gmail.com';
    $from = getenv('SMTP_FROM_EMAIL') ?: $username;
    $fromName = getenv('SMTP_FROM_NAME') ?: 'Centro Médico La Quinta';
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        error_log('La configuración de correo PQRSF no tiene direcciones válidas: ' . $reference);
        return false;
    }

    try {
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mailer->Port = (int) (getenv('SMTP_PORT') ?: 587);
        $mailer->SMTPAuth = true;
        $mailer->Username = $username;
        $mailer->Password = $password;
        $mailer->CharSet = 'UTF-8';
        $mailer->isHTML(true);
        $mailer->SMTPSecure = strtolower((string) getenv('SMTP_ENCRYPTION')) === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->setFrom($from, $fromName);
        $mailer->addAddress($recipient);
        $mailer->addReplyTo($values['email'], $values['full_name']);
        $mailer->Subject = $message['subject'];
        $mailer->Body = $message['html'];
        $mailer->AltBody = $message['text'];
        return $mailer->send();
    } catch (Throwable $exception) {
        error_log('No fue posible enviar la notificación SMTP PQRSF ' . $reference . ': ' . $exception->getMessage());
        return false;
    }
}
