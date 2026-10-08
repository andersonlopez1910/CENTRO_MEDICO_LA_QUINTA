<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/services.php';
require_once __DIR__ . '/../includes/pqrsf.php';
require_once __DIR__ . '/../includes/videos.php';
require_admin();
$postCount = (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$documentCount = (int) db()->query('SELECT COUNT(*) FROM documentos_pdf')->fetchColumn();
$serviceCount = service_count();
$pqrsfCount = pqrsf_count();
$videoCount = video_count();
$pageTitle = 'Panel de administración';
require __DIR__ . '/header.php';
?>
<div class="admin-content">
    <div class="admin-heading"><div><p class="eyebrow">Resumen</p><h1>Hola, <?= e($_SESSION['admin_username'] ?? 'administrador') ?></h1><p>Gestiona los servicios, novedades, documentos y solicitudes recibidas en el sitio.</p></div><a class="admin-button admin-button--primary" href="services.php">Crear servicio <span aria-hidden="true">+</span></a></div>
    <div class="stat-grid">
        <a class="stat-card" href="pqrsf.php"><span class="stat-icon stat-icon--blue">PQ</span><span class="stat-label">PQRSF recibidas</span><strong><?= $pqrsfCount ?></strong><span class="stat-link">Consultar radicados →</span></a>
        <a class="stat-card" href="videos.php"><span class="stat-icon stat-icon--green">▶</span><span class="stat-label">Videos</span><strong><?= $videoCount ?></strong><span class="stat-link">Administrar videos →</span></a>
        <a class="stat-card" href="services.php"><span class="stat-icon stat-icon--green">✚</span><span class="stat-label">Servicios</span><strong><?= $serviceCount ?></strong><span class="stat-link">Administrar servicios →</span></a>
        <a class="stat-card" href="posts.php"><span class="stat-icon stat-icon--blue">✎</span><span class="stat-label">Publicaciones</span><strong><?= $postCount ?></strong><span class="stat-link">Administrar publicaciones →</span></a>
        <a class="stat-card" href="documents.php"><span class="stat-icon stat-icon--green">PDF</span><span class="stat-label">Documentos PDF</span><strong><?= $documentCount ?></strong><span class="stat-link">Administrar documentos →</span></a>
    </div>
    <div class="admin-tip"><strong>Un sitio actualizado inspira confianza.</strong><p>Publica información útil y mantén los documentos importantes al alcance de tus pacientes.</p></div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
