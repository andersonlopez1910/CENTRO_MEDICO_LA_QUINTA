<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/pqrsf.php';
require_admin();

$pdo = db();
$statuses = pqrsf_statuses();
$types = pqrsf_types();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string) ($_POST['status'] ?? '');
    if (!$id || $id < 1 || !isset($statuses[$status])) {
        flash('error', 'No fue posible actualizar el estado de la solicitud.');
    } else {
        $update = $pdo->prepare('UPDATE pqrsf SET status = :status WHERE id = :id');
        $update->execute(['status' => $status, 'id' => $id]);
        flash('success', 'El estado de la PQRSF se actualizó correctamente.');
    }
    redirect('pqrsf.php?view=' . (int) $id);
}

$selected = null;
$viewId = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);
if ($viewId && $viewId > 0) {
    $find = $pdo->prepare('SELECT * FROM pqrsf WHERE id = :id');
    $find->execute(['id' => $viewId]);
    $selected = $find->fetch() ?: null;
}
$requests = $pdo->query('SELECT id, reference_code, request_type, full_name, subject, status, created_at FROM pqrsf ORDER BY created_at DESC, id DESC')->fetchAll();
$pageTitle = 'PQRSF';
require __DIR__ . '/header.php';
?>
<div class="admin-content admin-content--wide">
    <div class="admin-heading"><div><a class="admin-back" href="index.php">← Panel</a><p class="eyebrow">Atención al usuario</p><h1>PQRSF</h1><p>Consulta el detalle de las solicitudes recibidas y actualiza su estado de atención.</p></div></div>
    <div class="admin-columns admin-columns--pqrsf">
        <section class="admin-panel">
            <div class="panel-heading"><h2>Radicados recibidos</h2><span class="count-pill"><?= count($requests) ?></span></div>
            <?php if ($requests === []): ?><p class="panel-empty">No hay PQRSF registradas todavía.</p><?php else: ?><div class="admin-table-wrap"><table class="admin-table pqrsf-table"><thead><tr><th>Radicado</th><th>Solicitud</th><th>Fecha</th><th>Estado</th><th><span class="sr-only">Ver</span></th></tr></thead><tbody><?php foreach ($requests as $request): ?><tr><td class="table-title"><?= e($request['reference_code']) ?><br><span class="table-subtext"><?= e($request['full_name']) ?></span></td><td><?= e($types[$request['request_type']] ?? '') ?><br><span class="table-subtext"><?= e($request['subject']) ?></span></td><td><?= e(format_date($request['created_at'])) ?></td><td><span class="status-badge status-badge--<?= e($request['status']) ?>"><?= e($statuses[$request['status']] ?? '') ?></span></td><td><a class="text-button" href="pqrsf.php?view=<?= (int) $request['id'] ?>">Ver</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </section>
        <section class="admin-panel pqrsf-detail">
            <?php if ($selected === null): ?><h2>Detalle de la solicitud</h2><p class="panel-empty">Selecciona un radicado para revisar la información registrada.</p><?php else: ?>
                <div class="panel-heading"><div><p class="eyebrow">Radicado</p><h2><?= e($selected['reference_code']) ?></h2></div><span class="status-badge status-badge--<?= e($selected['status']) ?>"><?= e($statuses[$selected['status']] ?? '') ?></span></div>
                <dl class="pqrsf-detail-list"><div><dt>Tipo de solicitud</dt><dd><?= e($types[$selected['request_type']] ?? '') ?></dd></div><div><dt>Fecha de registro</dt><dd><?= e(format_date($selected['created_at'])) ?></dd></div><div><dt>Nombre completo</dt><dd><?= e($selected['full_name']) ?></dd></div><div><dt>Documento</dt><dd><?= e($selected['document_type']) ?> · <?= e($selected['document_number']) ?></dd></div><div><dt>Correo</dt><dd><a href="mailto:<?= e($selected['email']) ?>"><?= e($selected['email']) ?></a></dd></div><div><dt>Teléfono</dt><dd><a href="tel:<?= e($selected['phone']) ?>"><?= e($selected['phone']) ?></a></dd></div><div class="pqrsf-detail-list__full"><dt>Asunto</dt><dd><?= e($selected['subject']) ?></dd></div><div class="pqrsf-detail-list__full"><dt>Detalle</dt><dd class="pqrsf-message"><?= nl2br(e($selected['message'])) ?></dd></div></dl>
                <form class="status-form" method="post" action="pqrsf.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $selected['id'] ?>"><label for="status">Estado de atención</label><select id="status" name="status"><?php foreach ($statuses as $key => $label): ?><option value="<?= e($key) ?>" <?= $selected['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><button class="admin-button admin-button--primary" type="submit">Actualizar estado</button></form>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
