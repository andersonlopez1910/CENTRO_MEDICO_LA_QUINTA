<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/google_oauth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'No se pudo iniciar la autorización de Google. Intente de nuevo.');
    redirect('configuracion-api.php');
}
verify_csrf();

try {
    $settings = google_oauth_settings();
    if (!$settings || empty($settings['client_id']) || empty($settings['client_secret'])) {
        flash('error', 'Guarde primero el Client ID y Client Secret de Google Cloud.');
        redirect('configuracion-api.php');
    }

    $state = bin2hex(random_bytes(32));
    $verifier = google_oauth_pkce_verifier();
    $_SESSION['google_oauth_state'] = $state;
    $_SESSION['google_oauth_verifier'] = $verifier;
    $_SESSION['google_oauth_state_issued_at'] = time();

    header('Location: ' . google_oauth_authorization_url($settings, $state, $verifier));
    exit;
} catch (Throwable $exception) {
    error_log('No se pudo iniciar OAuth de Google: ' . $exception->getMessage());
    flash('error', 'No se pudo iniciar la autorización con Google.');
    redirect('configuracion-api.php');
}
