<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$pdo = db();
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS servicios (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(120) NOT NULL,
        description TEXT NOT NULL,
        icon VARCHAR(12) NOT NULL DEFAULT '✚',
        sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_servicios_status_order (status, sort_order, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$services = [
    ['Consulta Médica General', 'Valoración médica integral orientada al diagnóstico, tratamiento oportuno y prevención de enfermedades de primer nivel, promoviendo la salud continua del paciente y su familia.', '🩺', 10],
    ['Odontología', 'Atención en salud oral preventiva y correctiva, ofreciendo limpieza, restauración, profilaxis y cuidado integral para mantener una sonrisa sana.', '🦷', 20],
    ['Ecografía', 'Estudio de diagnóstico por imagen por ultrasonido de alta precisión para la evaluación detallada de órganos internos, tejidos blandos y seguimiento gestacional.', '〰', 30],
    ['Radiografía', 'Servicio de radiología digital eficiente para la toma e interpretación de imágenes diagnósticas óseas y torácicas, garantizando resultados oportunos.', '☢', 40],
    ['Dermatología', 'Diagnóstico, tratamiento y cuidado especializado para afecciones de la piel, cabello y uñas, orientado a la salud dermatológica y estética clínica.', '✦', 50],
    ['Nutrición', 'Evaluación antropométrica y elaboración de planes de alimentación personalizados para el control de peso, manejo de condiciones metabólicas y hábitos de vida saludable.', '🍃', 60],
    ['Ortopedia', 'Valoración especializada del sistema musculoesquelético para la prevención, diagnóstico y manejo de lesiones en articulaciones, huesos y ligamentos.', '🦴', 70],
    ['Psicología', 'Acompañamiento profesional y terapia psicológica enfocada en la salud mental, gestión emocional y bienestar psicológico individual o familiar.', '♡', 80],
    ['Gastroenterología', 'Atención especializada en la prevención, diagnóstico y tratamiento de trastornos y enfermedades del sistema digestivo, estómago e intestinos.', '◉', 90],
    ['Terapias Respiratorias', 'Tratamiento integral para la rehabilitación y manejo de patologías pulmonares y respiratorias agudas o crónicas en adultos y niños.', '≋', 100],
    ['Cardiología', 'Evaluación y control preventivo y terapéutico de la salud cardiovascular, arritmias e hipertensión arterial para un corazón sano.', '♥', 110],
    ['Nebulización e Inyectología', 'Procedimientos ambulatorios asistidos por personal de enfermería calificado para la administración segura de medicamentos inhalados e inyectables bajo prescripción médica.', '✚', 120],
    ['Droguería', 'Suministro y dispensación oportuna de medicamentos éticos, genéricos y material de insumos médicos de primera necesidad, garantizando estándares de calidad, seguridad y asesoría farmacéutica para el paciente.', '💊', 130],
];

$pdo->beginTransaction();
try {
    $removeLegacy = $pdo->prepare('DELETE FROM servicios WHERE title IN (?, ?, ?)');
    $removeLegacy->execute(['Consulta médica', 'Apoyo diagnóstico', 'Bienestar integral']);
    $existingTitles = $pdo->query('SELECT title FROM servicios')->fetchAll(PDO::FETCH_COLUMN);
    $insert = $pdo->prepare('INSERT INTO servicios (title, description, icon, sort_order, status) VALUES (?, ?, ?, ?, \'published\')');
    foreach ($services as $service) {
        if (!in_array($service[0], $existingTitles, true)) {
            $insert->execute($service);
        }
    }
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

fwrite(STDOUT, "Migración de servicios completada.\n");
