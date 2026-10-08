<?php
declare(strict_types=1);

/** @return array<string, string> */
function pqrsf_types(): array
{
    return ['petition' => 'Petición', 'complaint' => 'Queja', 'claim' => 'Reclamo', 'suggestion' => 'Sugerencia', 'compliment' => 'Felicitación'];
}

/** @return array<string, string> */
function pqrsf_document_types(): array
{
    return ['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'TI' => 'Tarjeta de identidad', 'PA' => 'Pasaporte', 'OT' => 'Otro'];
}

/** @return array<string, string> */
function pqrsf_statuses(): array
{
    return ['received' => 'Recibido', 'in_progress' => 'En trámite', 'closed' => 'Cerrado'];
}

function pqrsf_reference(): string
{
    return 'PQRSF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
}

function pqrsf_count(): int
{
    try {
        return (int) db()->query('SELECT COUNT(*) FROM pqrsf')->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

/** @param array<string, string> $values
 *  @return array<string, string>
 */
function validate_pqrsf_values(array $values, bool $dataPolicyAccepted): array
{
    $errors = [];
    $types = pqrsf_types();
    $documentTypes = pqrsf_document_types();
    if (!isset($types[$values['request_type'] ?? ''])) $errors['request_type'] = 'Selecciona el tipo de solicitud.';
    if (!isset($documentTypes[$values['document_type'] ?? ''])) $errors['document_type'] = 'Selecciona el tipo de documento.';
    if (!preg_match('/\A[A-Za-z0-9.\-]{5,30}\z/', $values['document_number'] ?? '')) $errors['document_number'] = 'Escribe un número de documento válido.';
    if (text_length($values['full_name'] ?? '') < 3 || text_length($values['full_name'] ?? '') > 150) $errors['full_name'] = 'Escribe tu nombre completo, con máximo 150 caracteres.';
    if (!filter_var($values['email'] ?? '', FILTER_VALIDATE_EMAIL) || text_length($values['email'] ?? '') > 254) $errors['email'] = 'Escribe un correo electrónico válido.';
    if (!preg_match('/\A[0-9+() .\-]{7,30}\z/', $values['phone'] ?? '')) $errors['phone'] = 'Escribe un teléfono de contacto válido.';
    if (text_length($values['subject'] ?? '') < 5 || text_length($values['subject'] ?? '') > 180) $errors['subject'] = 'El asunto debe tener entre 5 y 180 caracteres.';
    if (text_length($values['message'] ?? '') < 20 || text_length($values['message'] ?? '') > 5000) $errors['message'] = 'Describe tu solicitud en un texto de 20 a 5.000 caracteres.';
    if (!$dataPolicyAccepted) $errors['data_policy'] = 'Debes aceptar el tratamiento de datos personales para enviar la solicitud.';
    return $errors;
}

/** @param array<string, string> $values */
function save_pqrsf(array $values): string
{
    $reference = pqrsf_reference();
    $insert = db()->prepare('INSERT INTO pqrsf (reference_code, request_type, document_type, document_number, full_name, email, phone, subject, message) VALUES (:reference, :request_type, :document_type, :document_number, :full_name, :email, :phone, :subject, :message)');
    $insert->execute(['reference' => $reference] + $values);
    return $reference;
}

/** @param array<string, string> $values */
function notify_pqrsf(string $reference, array $values): bool
{
    require_once __DIR__ . '/gmail_api.php';
    $gmailResult = send_pqrsf_gmail_api_notification($reference, $values);
    db()->prepare('UPDATE pqrsf SET notification_status = :status, notification_attempted_at = NOW() WHERE reference_code = :reference')
        ->execute(['status' => $gmailResult ? 'sent' : 'failed', 'reference' => $reference]);
    return $gmailResult;
}
