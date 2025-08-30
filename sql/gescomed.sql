-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-08-2025 a las 22:33:33
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gescomed`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cita_agendar` (IN `p_id_paciente` INT, IN `p_id_medico` INT, IN `p_fecha_cita` DATE, IN `p_hora_cita` TIME, OUT `p_resultado` VARCHAR(255))   BEGIN
    DECLARE cita_existente INT;
    SELECT COUNT(*) INTO cita_existente FROM cita
    WHERE id_medico = p_id_medico AND fecha_cita = p_fecha_cita AND hora_cita = p_hora_cita AND estado != 'cancelada';

    IF cita_existente = 0 THEN
        INSERT INTO cita (id_paciente, id_medico, fecha_cita, hora_cita, estado)
        VALUES (p_id_paciente, p_id_medico, p_fecha_cita, p_hora_cita, 'pendiente');
        SET p_resultado = 'Cita agendada exitosamente.';
    ELSE
        SET p_resultado = 'Error: El médico ya tiene una cita programada en esa fecha y hora.';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_factura_generar` (IN `p_id_cita` INT)   BEGIN
    DECLARE v_monto_consulta DECIMAL(10, 2);
    DECLARE v_factura_existente INT;
    SELECT COUNT(*) INTO v_factura_existente FROM factura WHERE id_cita = p_id_cita;

    IF v_factura_existente = 0 THEN
        SET v_monto_consulta = 150.00; -- Costo fijo de ejemplo
        -- VERSIÓN CORREGIDA: Se eliminó el valor duplicado
        INSERT INTO factura (id_cita, monto_total, estado_pago, fecha_emision)
        VALUES (p_id_cita, v_monto_consulta, 'pendiente', CURDATE());
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_paciente_crud` (IN `p_operacion` VARCHAR(10), IN `p_id_paciente` INT, IN `p_nombre` VARCHAR(50), IN `p_apellido` VARCHAR(50), IN `p_fecha_nacimiento` DATE, IN `p_email` VARCHAR(100))   BEGIN
    IF p_operacion = 'create' THEN
        INSERT INTO paciente (nombre, apellido, fecha_nacimiento, email)
        VALUES (p_nombre, p_apellido, p_fecha_nacimiento, p_email);
    END IF;
    -- (Aquí la lógica de UPDATE y DELETE se modificaría de forma similar)
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_contar_citas_medico` (`p_id_medico` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE total_citas INT;
    SELECT COUNT(*)
    INTO total_citas
    FROM cita
    WHERE id_medico = p_id_medico AND estado = 'atendida';
    RETURN total_citas;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_obtener_edad_paciente` (`p_fecha_nacimiento` DATE) RETURNS INT(11) DETERMINISTIC BEGIN
    RETURN TIMESTAMPDIFF(YEAR, p_fecha_nacimiento, CURDATE());
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cita`
--

CREATE TABLE `cita` (
  `id_cita` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `id_medico` int(11) NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_cita` time NOT NULL,
  `estado` enum('pendiente','atendida','cancelada') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cita`
--

INSERT INTO `cita` (`id_cita`, `id_paciente`, `id_medico`, `fecha_cita`, `hora_cita`, `estado`) VALUES
(1, 1, 1, '2025-09-10', '10:00:00', 'atendida'),
(2, 2, 2, '2025-09-10', '11:30:00', 'atendida'),
(3, 3, 3, '2025-09-11', '09:00:00', 'pendiente'),
(4, 4, 1, '2025-09-12', '10:30:00', 'cancelada'),
(5, 5, 4, '2025-09-15', '14:00:00', 'pendiente'),
(6, 6, 5, '2025-08-20', '16:00:00', 'atendida'),
(7, 7, 6, '2025-08-22', '09:30:00', 'atendida'),
(8, 8, 7, '2025-09-01', '15:00:00', 'atendida'),
(9, 9, 8, '2025-09-03', '12:00:00', 'cancelada'),
(10, 10, 9, '2025-09-05', '11:00:00', 'pendiente'),
(11, 1, 10, '2025-09-18', '17:00:00', 'pendiente'),
(12, 2, 3, '2025-08-28', '10:00:00', 'atendida'),
(13, 11, 2, '2025-09-20', '14:30:00', 'pendiente'),
(14, 12, 6, '2025-07-15', '08:00:00', 'atendida'),
(15, 4, 5, '2025-07-30', '16:30:00', 'atendida'),
(16, 11, 2, '2025-08-30', '08:18:00', 'pendiente'),
(17, 13, 1, '2025-08-30', '08:00:00', 'atendida'),
(18, 5, 2, '2025-09-24', '18:00:00', 'pendiente'),
(19, 8, 8, '2025-08-31', '09:30:00', 'pendiente'),
(20, 16, 4, '2025-08-30', '09:00:00', 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultorio`
--

CREATE TABLE `consultorio` (
  `id_consultorio` int(11) NOT NULL,
  `nombre_consultorio` varchar(100) NOT NULL,
  `ubicacion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `consultorio`
--

INSERT INTO `consultorio` (`id_consultorio`, `nombre_consultorio`, `ubicacion`) VALUES
(1, 'Consultorio 101', 'Piso 1, Ala Norte'),
(2, 'Consultorio 102', 'Piso 1, Ala Sur'),
(3, 'Consultorio 201', 'Piso 2, Ala Norte'),
(4, 'Consultorio 202', 'Piso 2, Ala Sur');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contacto_paciente`
--

CREATE TABLE `contacto_paciente` (
  `id_contacto` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `tipo_contacto` enum('telefono','direccion') NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidad`
--

CREATE TABLE `especialidad` (
  `id_especialidad` int(11) NOT NULL,
  `nombre_especialidad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidad`
--

INSERT INTO `especialidad` (`id_especialidad`, `nombre_especialidad`) VALUES
(1, 'Cardiología'),
(2, 'Dermatología'),
(3, 'Pediatría'),
(4, 'Neurología'),
(5, 'Ginecología'),
(6, 'Traumatología');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `id_factura` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `estado_pago` enum('pendiente','pagada') DEFAULT 'pendiente',
  `fecha_emision` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`id_factura`, `id_cita`, `monto_total`, `estado_pago`, `fecha_emision`) VALUES
(1, 1, 150.00, 'pagada', '2025-09-10'),
(2, 2, 250.00, 'pagada', '2025-09-10'),
(3, 6, 300.00, 'pendiente', '2025-08-21'),
(4, 7, 180.00, 'pagada', '2025-08-22'),
(5, 8, 220.00, 'pendiente', '2025-09-01'),
(6, 12, 150.00, 'pagada', '2025-08-28'),
(7, 14, 200.00, 'pagada', '2025-07-15'),
(8, 15, 150.00, 'pendiente', '2025-08-30'),
(9, 17, 150.00, 'pendiente', '2025-08-30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_clinico`
--

CREATE TABLE `historial_clinico` (
  `id_historial` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `diagnostico` text NOT NULL,
  `tratamiento` text DEFAULT NULL,
  `fecha_registro` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_clinico`
--

INSERT INTO `historial_clinico` (`id_historial`, `id_paciente`, `diagnostico`, `tratamiento`, `fecha_registro`) VALUES
(1, 1, 'Hipertensión arterial', 'Seguir dieta baja en sodio y medicación diaria.', '2025-09-10'),
(2, 2, 'Dermatitis atópica', 'Crema humectante y evitar alérgenos.', '2025-09-10'),
(3, 6, 'Migraña crónica', 'Analgésicos y seguimiento neurológico.', '2025-08-20'),
(4, 7, 'Fractura de cúbito', 'Inmovilización con yeso por 6 semanas.', '2025-08-22'),
(5, 12, 'Artritis reumatoide', 'Terapia física y antiinflamatorios.', '2025-07-15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medico`
--

CREATE TABLE `medico` (
  `id_medico` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `id_especialidad` int(11) NOT NULL,
  `id_consultorio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `medico`
--

INSERT INTO `medico` (`id_medico`, `nombre`, `apellido`, `telefono`, `email`, `id_especialidad`, `id_consultorio`) VALUES
(1, 'Carlos', 'Rivera', '77711122', 'carlos.rivera@email.com', 1, 1),
(2, 'Ana', 'Gomez', '77733344', 'ana.gomez@email.com', 2, 2),
(3, 'Luis', 'Martinez', '77755566', 'luis.martinez@email.com', 3, 3),
(4, 'Sofia', 'Lopez', '77777788', 'sofia.lopez@email.com', 1, 4),
(5, 'Juan', 'Hernandez', '77799900', 'juan.hdz@email.com', 4, 1),
(6, 'Elena', 'Morales', '77712345', 'elena.m@email.com', 5, 2),
(7, 'Miguel', 'Castillo', '77765432', 'miguel.c@email.com', 6, 3),
(8, 'Isabel', 'Rojas', '77711223', 'isabel.r@email.com', 2, 4),
(9, 'David', 'Flores', '77733445', 'david.f@email.com', 3, 1),
(10, 'Valeria', 'Nuñez', '77755667', 'valeria.n@email.com', 4, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paciente`
--

CREATE TABLE `paciente` (
  `id_paciente` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paciente`
--

INSERT INTO `paciente` (`id_paciente`, `nombre`, `apellido`, `fecha_nacimiento`, `email`) VALUES
(1, 'Maria', 'Gonzalez', '1990-05-15', 'maria.g@email.com'),
(2, 'Jose', 'Rodriguez', '1985-11-20', 'jose.r@email.com'),
(3, 'Laura', 'Perez', '2018-02-10', 'laura.p@email.com'),
(4, 'Pedro', 'Sanchez', '1973-07-30', 'pedro.s@email.com'),
(5, 'Elena', 'Ramirez', '1995-09-01', 'elena.r@email.com'),
(6, 'Marcos', 'Diaz', '2005-03-12', 'marcos.d@email.com'),
(7, 'Lucia', 'Vega', '1964-12-25', 'lucia.v@email.com'),
(8, 'Javier', 'Soria', '1998-08-08', 'javier.s@email.com'),
(9, 'Carmen', 'Mendoza', '1982-04-19', 'carmen.m@email.com'),
(10, 'Ricardo', 'Torres', '2015-06-07', 'ricardo.t@email.com'),
(11, 'Adriana', 'Guzman', '1991-10-30', 'adriana.g@email.com'),
(12, 'Roberto', 'Quispe', '1955-01-22', 'roberto.q@email.com'),
(13, 'Gunnar Ludwing', 'Pecho Vallejos', '1990-09-24', 'lui@gmail.com'),
(15, 'Carlos', 'Toro', '1985-01-26', 'jcarlost@gmail.com'),
(16, 'Maria', 'Petraca', '1950-04-01', 'petraca@hotmail.com');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cita`
--
ALTER TABLE `cita`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `id_paciente` (`id_paciente`),
  ADD KEY `id_medico` (`id_medico`);

--
-- Indices de la tabla `consultorio`
--
ALTER TABLE `consultorio`
  ADD PRIMARY KEY (`id_consultorio`);

--
-- Indices de la tabla `contacto_paciente`
--
ALTER TABLE `contacto_paciente`
  ADD PRIMARY KEY (`id_contacto`),
  ADD KEY `id_paciente` (`id_paciente`);

--
-- Indices de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  ADD PRIMARY KEY (`id_especialidad`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`id_factura`),
  ADD KEY `id_cita` (`id_cita`);

--
-- Indices de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_paciente` (`id_paciente`);

--
-- Indices de la tabla `medico`
--
ALTER TABLE `medico`
  ADD PRIMARY KEY (`id_medico`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_consultorio` (`id_consultorio`),
  ADD KEY `idx_medico_especialidad` (`id_especialidad`);

--
-- Indices de la tabla `paciente`
--
ALTER TABLE `paciente`
  ADD PRIMARY KEY (`id_paciente`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cita`
--
ALTER TABLE `cita`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `consultorio`
--
ALTER TABLE `consultorio`
  MODIFY `id_consultorio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `contacto_paciente`
--
ALTER TABLE `contacto_paciente`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  MODIFY `id_especialidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `id_factura` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `medico`
--
ALTER TABLE `medico`
  MODIFY `id_medico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `paciente`
--
ALTER TABLE `paciente`
  MODIFY `id_paciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cita`
--
ALTER TABLE `cita`
  ADD CONSTRAINT `cita_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `paciente` (`id_paciente`),
  ADD CONSTRAINT `cita_ibfk_2` FOREIGN KEY (`id_medico`) REFERENCES `medico` (`id_medico`);

--
-- Filtros para la tabla `contacto_paciente`
--
ALTER TABLE `contacto_paciente`
  ADD CONSTRAINT `contacto_paciente_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `paciente` (`id_paciente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `factura_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `cita` (`id_cita`);

--
-- Filtros para la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD CONSTRAINT `historial_clinico_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `paciente` (`id_paciente`);

--
-- Filtros para la tabla `medico`
--
ALTER TABLE `medico`
  ADD CONSTRAINT `medico_ibfk_1` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_especialidad`),
  ADD CONSTRAINT `medico_ibfk_2` FOREIGN KEY (`id_consultorio`) REFERENCES `consultorio` (`id_consultorio`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
