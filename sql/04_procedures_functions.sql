-- Script para crear los Procedimientos Almacenados y Funciones de GESCOMED
USE gescomed;
DELIMITER $$

-- 1. PROCEDIMIENTOS ALMACENADOS

-- Procedimiento para gestionar pacientes (Crear, Actualizar y Eliminar)
DROP PROCEDURE IF EXISTS sp_paciente_crud;
DELIMITER $$
CREATE PROCEDURE `sp_paciente_crud`(
    IN p_operacion VARCHAR(10),
    IN p_id_paciente INT,
    IN p_nombre VARCHAR(50),
    IN p_apellido VARCHAR(50),
    IN p_fecha_nacimiento DATE,
    IN p_telefono VARCHAR(15),
    IN p_direccion VARCHAR(100),
    IN p_email VARCHAR(100)
)
BEGIN
    -- Lógica para CREAR un nuevo paciente
    IF p_operacion = 'create' THEN
        INSERT INTO paciente (nombre, apellido, fecha_nacimiento, telefono, direccion, email)
        VALUES (p_nombre, p_apellido, p_fecha_nacimiento, p_telefono, p_direccion, p_email);
    END IF;

    -- Lógica para ACTUALIZAR un paciente existente
    IF p_operacion = 'update' THEN
        UPDATE paciente
        SET
            nombre = p_nombre,
            apellido = p_apellido,
            fecha_nacimiento = p_fecha_nacimiento,
            telefono = p_telefono,
            direccion = p_direccion,
            email = p_email
        WHERE id_paciente = p_id_paciente;
    END IF;

    -- Lógica para ELIMINAR un paciente
    IF p_operacion = 'delete' THEN
        DELETE FROM paciente
        WHERE id_paciente = p_id_paciente;
    END IF;
END$$
DELIMITER ;

-- Procedimiento para agendar una cita con validación de disponibilidad
CREATE PROCEDURE sp_cita_agendar(
    IN p_id_paciente INT,
    IN p_id_medico INT,
    IN p_fecha_cita DATE,
    IN p_hora_cita TIME,
    OUT p_resultado VARCHAR(255)
)
BEGIN
    DECLARE cita_existente INT;
    SELECT COUNT(*) INTO cita_existente
    FROM cita
    WHERE id_medico = p_id_medico AND fecha_cita = p_fecha_cita AND hora_cita = p_hora_cita AND estado != 'cancelada';

    IF cita_existente = 0 THEN
        INSERT INTO cita (id_paciente, id_medico, fecha_cita, hora_cita, estado)
        VALUES (p_id_paciente, p_id_medico, p_fecha_cita, p_hora_cita, 'pendiente');
        SET p_resultado = 'Cita agendada exitosamente.';
    ELSE
        SET p_resultado = 'Error: El médico ya tiene una cita programada en esa fecha y hora.';
    END IF;
END$$

-- Procedimiento para generar una factura a partir de una cita
CREATE PROCEDURE sp_factura_generar(
    IN p_id_cita INT
)
BEGIN
    DECLARE v_monto_consulta DECIMAL(10, 2);
    DECLARE v_factura_existente INT;
    SELECT COUNT(*) INTO v_factura_existente FROM factura WHERE id_cita = p_id_cita;

    IF v_factura_existente = 0 THEN
        SET v_monto_consulta = 150.00; -- Costo fijo de ejemplo
        INSERT INTO factura (id_cita, monto_total, estado_pago, fecha_emision)
        VALUES (p_id_cita, v_monto_consulta, 'pendiente', CURDATE());
    END IF;
END$$

-- 2. FUNCIONES

-- Función para calcular la edad de un paciente
CREATE FUNCTION fn_obtener_edad_paciente(
    p_fecha_nacimiento DATE
)
RETURNS INT
DETERMINISTIC
BEGIN
    RETURN TIMESTAMPDIFF(YEAR, p_fecha_nacimiento, CURDATE());
END$$

-- Función que retorna el número de citas atendidas por un médico
CREATE FUNCTION fn_contar_citas_medico(
    p_id_medico INT
)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total_citas INT;
    SELECT COUNT(*)
    INTO total_citas
    FROM cita
    WHERE id_medico = p_id_medico AND estado = 'atendida';
    RETURN total_citas;
END$$

DELIMITER ;