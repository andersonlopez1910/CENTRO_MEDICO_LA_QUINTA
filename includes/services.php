<?php
declare(strict_types=1);

/**
 * Fallback content shown until the SQL schema has been imported. It also keeps
 * the public site useful if the service table is temporarily unavailable.
 *
 * @return list<array{id:int,title:string,description:string,icon:string}>
 */
function default_services(): array
{
    return [
        ['id' => 0, 'title' => 'Consulta Médica General', 'description' => 'Valoración médica integral orientada al diagnóstico, tratamiento oportuno y prevención de enfermedades de primer nivel, promoviendo la salud continua del paciente y su familia.', 'icon' => '🩺'],
        ['id' => 0, 'title' => 'Odontología', 'description' => 'Atención en salud oral preventiva y correctiva, ofreciendo limpieza, restauración, profilaxis y cuidado integral para mantener una sonrisa sana.', 'icon' => '🦷'],
        ['id' => 0, 'title' => 'Ecografía', 'description' => 'Estudio de diagnóstico por imagen por ultrasonido de alta precisión para la evaluación detallada de órganos internos, tejidos blandos y seguimiento gestacional.', 'icon' => '〰'],
        ['id' => 0, 'title' => 'Radiografía', 'description' => 'Servicio de radiología digital eficiente para la toma e interpretación de imágenes diagnósticas óseas y torácicas, garantizando resultados oportunos.', 'icon' => '☢'],
        ['id' => 0, 'title' => 'Dermatología', 'description' => 'Diagnóstico, tratamiento y cuidado especializado para afecciones de la piel, cabello y uñas, orientado a la salud dermatológica y estética clínica.', 'icon' => '✦'],
        ['id' => 0, 'title' => 'Nutrición', 'description' => 'Evaluación antropométrica y elaboración de planes de alimentación personalizados para el control de peso, manejo de condiciones metabólicas y hábitos de vida saludable.', 'icon' => '🍃'],
        ['id' => 0, 'title' => 'Ortopedia', 'description' => 'Valoración especializada del sistema musculoesquelético para la prevención, diagnóstico y manejo de lesiones en articulaciones, huesos y ligamentos.', 'icon' => '🦴'],
        ['id' => 0, 'title' => 'Psicología', 'description' => 'Acompañamiento profesional y terapia psicológica enfocada en la salud mental, gestión emocional y bienestar psicológico individual o familiar.', 'icon' => '♡'],
        ['id' => 0, 'title' => 'Gastroenterología', 'description' => 'Atención especializada en la prevención, diagnóstico y tratamiento de trastornos y enfermedades del sistema digestivo, estómago e intestinos.', 'icon' => '◉'],
        ['id' => 0, 'title' => 'Terapias Respiratorias', 'description' => 'Tratamiento integral para la rehabilitación y manejo de patologías pulmonares y respiratorias agudas o crónicas en adultos y niños.', 'icon' => '≋'],
        ['id' => 0, 'title' => 'Cardiología', 'description' => 'Evaluación y control preventivo y terapéutico de la salud cardiovascular, arritmias e hipertensión arterial para un corazón sano.', 'icon' => '♥'],
        ['id' => 0, 'title' => 'Nebulización e Inyectología', 'description' => 'Procedimientos ambulatorios asistidos por personal de enfermería calificado para la administración segura de medicamentos inhalados e inyectables bajo prescripción médica.', 'icon' => '✚'],
        ['id' => 0, 'title' => 'Droguería', 'description' => 'Suministro y dispensación oportuna de medicamentos éticos, genéricos y material de insumos médicos de primera necesidad, garantizando estándares de calidad, seguridad y asesoría farmacéutica para el paciente.', 'icon' => '💊'],
    ];
}

/** @return list<array{id:int,title:string,description:string,icon:string}> */
function public_services(?int $limit = null): array
{
    try {
        $sql = "SELECT id, title, description, icon FROM servicios WHERE status = 'published' ORDER BY sort_order ASC, id ASC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, min($limit, 12));
        }
        $services = db()->query($sql)->fetchAll();
        return $services !== [] ? $services : default_services();
    } catch (PDOException) {
        return default_services();
    }
}

function service_count(): int
{
    try {
        return (int) db()->query('SELECT COUNT(*) FROM servicios')->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}
