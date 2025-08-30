-- Script con las 6 consultas para los reportes analíticos de GESCOMED
USE gescomed;

-- 1. Reporte de Ingresos por Especialidad Médica
-- Suma los montos de las facturas pagadas, agrupados por la especialidad del médico.
SELECT 
    e.nombre_especialidad,
    SUM(f.monto_total) AS total_ingresos
FROM factura f
JOIN cita c ON f.id_cita = c.id_cita
JOIN medico m ON c.id_medico = m.id_medico
JOIN especialidad e ON m.id_especialidad = e.id_especialidad
WHERE f.estado_pago = 'pagada'
GROUP BY e.nombre_especialidad
ORDER BY total_ingresos DESC;


-- 2. Reporte de Citas por Médico
-- Cuenta cuántas citas ha atendido cada médico.
SELECT
    CONCAT(m.nombre, ' ', m.apellido) AS nombre_medico,
    (SELECT fn_contar_citas_medico(m.id_medico)) AS citas_atendidas
FROM medico m
ORDER BY citas_atendidas DESC;


-- 3. Reporte de Pacientes por Rango de Edad
-- Cuenta cuántos pacientes hay en diferentes rangos de edad.
SELECT
    CASE
        WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 0 AND 17 THEN '0-17 (Niños/Adolescentes)'
        WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 18 AND 35 THEN '18-35 (Jóvenes Adultos)'
        WHEN fn_obtener_edad_paciente(fecha_nacimiento) BETWEEN 36 AND 60 THEN '36-60 (Adultos)'
        ELSE '61+ (Adultos Mayores)'
    END AS rango_edad,
    COUNT(id_paciente) AS numero_de_pacientes
FROM paciente
GROUP BY rango_edad
ORDER BY rango_edad;


-- 4. Reporte de Citas por Mes (Último Año)
-- Muestra la cantidad de citas atendidas cada mes durante el último año.
SELECT 
    DATE_FORMAT(fecha_cita, '%Y-%m') AS mes,
    COUNT(id_cita) AS cantidad_citas
FROM cita
WHERE estado = 'atendida' AND fecha_cita >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
GROUP BY mes
ORDER BY mes ASC;


-- 5. Reporte de Historial Completo de un Paciente
-- Muestra todas las citas, diagnósticos y facturas de un paciente específico (ej: id_paciente = 1).
SET @id_paciente_buscado = 1;
SELECT 
    'Cita' AS tipo_registro,
    c.fecha_cita AS fecha,
    CONCAT('Cita con Dr. ', m.apellido, ' (', e.nombre_especialidad, ')') AS descripcion,
    c.estado AS detalles
FROM cita c
JOIN medico m ON c.id_medico = m.id_medico
JOIN especialidad e ON m.id_especialidad = e.id_especialidad
WHERE c.id_paciente = @id_paciente_buscado
UNION ALL
SELECT
    'Diagnóstico' AS tipo_registro,
    hc.fecha_registro AS fecha,
    hc.diagnostico AS descripcion,
    CONCAT('Tratamiento: ', hc.tratamiento) AS detalles
FROM historial_clinico hc
WHERE hc.id_paciente = @id_paciente_buscado
UNION ALL
SELECT
    'Factura' AS tipo_registro,
    f.fecha_emision AS fecha,
    CONCAT('Factura por consulta del ', c.fecha_cita) AS descripcion,
    CONCAT('Monto: ', f.monto_total, ', Estado: ', f.estado_pago) AS detalles
FROM factura f
JOIN cita c ON f.id_cita = c.id_cita
WHERE c.id_paciente = @id_paciente_buscado
ORDER BY fecha DESC;


-- 6. Reporte de Facturas Pendientes de Pago
-- Muestra un listado de todas las facturas que no han sido pagadas.
SELECT
    f.id_factura,
    f.fecha_emision,
    f.monto_total,
    CONCAT(p.nombre, ' ', p.apellido) AS nombre_paciente,
    p.telefono AS telefono_paciente
FROM factura f
JOIN cita c ON f.id_cita = c.id_cita
JOIN paciente p ON c.id_paciente = p.id_paciente
WHERE f.estado_pago = 'pendiente'
ORDER BY f.fecha_emision ASC;