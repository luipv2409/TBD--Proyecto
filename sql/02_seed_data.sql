-- Script para insertar datos de prueba (SEED) en la base de datos GESCOMED
-- Versión ampliada para cumplir con el requisito de +10 registros
USE gescomed;

-- Vaciar tablas para evitar duplicados si se ejecuta de nuevo
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE factura;
TRUNCATE TABLE historial_clinico;
TRUNCATE TABLE cita;
TRUNCATE TABLE paciente;
TRUNCATE TABLE medico;
TRUNCATE TABLE consultorio;
TRUNCATE TABLE especialidad;
SET FOREIGN_KEY_CHECKS = 1;

-- Insertar Especialidades (5)
INSERT INTO especialidad (nombre_especialidad) VALUES 
('Cardiología'), ('Dermatología'), ('Pediatría'), ('Neurología'), ('Ginecología'), ('Traumatología');

-- Insertar Consultorios (4)
INSERT INTO consultorio (nombre_consultorio, ubicacion) VALUES
('Consultorio 101', 'Piso 1, Ala Norte'), ('Consultorio 102', 'Piso 1, Ala Sur'),
('Consultorio 201', 'Piso 2, Ala Norte'), ('Consultorio 202', 'Piso 2, Ala Sur');

-- Insertar Médicos (10)
INSERT INTO medico (nombre, apellido, telefono, email, id_especialidad, id_consultorio) VALUES
('Carlos', 'Rivera', '77711122', 'carlos.rivera@email.com', 1, 1),
('Ana', 'Gomez', '77733344', 'ana.gomez@email.com', 2, 2),
('Luis', 'Martinez', '77755566', 'luis.martinez@email.com', 3, 3),
('Sofia', 'Lopez', '77777788', 'sofia.lopez@email.com', 1, 4),
('Juan', 'Hernandez', '77799900', 'juan.hdz@email.com', 4, 1),
('Elena', 'Morales', '77712345', 'elena.m@email.com', 5, 2),
('Miguel', 'Castillo', '77765432', 'miguel.c@email.com', 6, 3),
('Isabel', 'Rojas', '77711223', 'isabel.r@email.com', 2, 4),
('David', 'Flores', '77733445', 'david.f@email.com', 3, 1),
('Valeria', 'Nuñez', '77755667', 'valeria.n@email.com', 4, 2);

-- Insertar Pacientes (12)
INSERT INTO paciente (nombre, apellido, fecha_nacimiento, telefono, direccion, email) VALUES
('Maria', 'Gonzalez', '1990-05-15', '66611122', 'Calle Falsa 123', 'maria.g@email.com'),
('Jose', 'Rodriguez', '1985-11-20', '66633344', 'Avenida Siempreviva 742', 'jose.r@email.com'),
('Laura', 'Perez', '2018-02-10', '66655566', 'Plaza Mayor 1', 'laura.p@email.com'),
('Pedro', 'Sanchez', '1973-07-30', '66677788', 'Calle Luna 24', 'pedro.s@email.com'),
('Elena', 'Ramirez', '1995-09-01', '66699900', 'Avenida del Sol 50', 'elena.r@email.com'),
('Marcos', 'Diaz', '2005-03-12', '66612121', 'Calle Pino 8', 'marcos.d@email.com'),
('Lucia', 'Vega', '1964-12-25', '66634343', 'Avenida Roble 110', 'lucia.v@email.com'),
('Javier', 'Soria', '1998-08-08', '66656565', 'Calle Girasol 33', 'javier.s@email.com'),
('Carmen', 'Mendoza', '1982-04-19', '66678787', 'Plaza Central 2', 'carmen.m@email.com'),
('Ricardo', 'Torres', '2015-06-07', '66690909', 'Avenida del Prado 78', 'ricardo.t@email.com'),
('Adriana', 'Guzman', '1991-10-30', '66613131', 'Calle 10 de Mayo', 'adriana.g@email.com'),
('Roberto', 'Quispe', '1955-01-22', '66614141', 'Avenida Villarroel 99', 'roberto.q@email.com');

-- Insertar Citas (15)
INSERT INTO cita (id_paciente, id_medico, fecha_cita, hora_cita, estado) VALUES
(1, 1, '2025-09-10', '10:00:00', 'atendida'),
(2, 2, '2025-09-10', '11:30:00', 'atendida'),
(3, 3, '2025-09-11', '09:00:00', 'pendiente'),
(4, 1, '2025-09-12', '10:30:00', 'cancelada'),
(5, 4, '2025-09-15', '14:00:00', 'pendiente'),
(6, 5, '2025-08-20', '16:00:00', 'atendida'),
(7, 6, '2025-08-22', '09:30:00', 'atendida'),
(8, 7, '2025-09-01', '15:00:00', 'atendida'),
(9, 8, '2025-09-03', '12:00:00', 'cancelada'),
(10, 9, '2025-09-05', '11:00:00', 'pendiente'),
(1, 10, '2025-09-18', '17:00:00', 'pendiente'),
(2, 3, '2025-08-28', '10:00:00', 'atendida'),
(11, 2, '2025-09-20', '14:30:00', 'pendiente'),
(12, 6, '2025-07-15', '08:00:00', 'atendida'),
(4, 5, '2025-07-30', '16:30:00', 'atendida');

-- Insertar Facturas (para algunas citas atendidas)
INSERT INTO factura (id_cita, monto_total, estado_pago, fecha_emision) VALUES
(1, 150.00, 'pagada', '2025-09-10'),
(2, 250.00, 'pagada', '2025-09-10'),
(6, 300.00, 'pendiente', '2025-08-21'),
(7, 180.00, 'pagada', '2025-08-22'),
(8, 220.00, 'pendiente', '2025-09-01'),
(12, 150.00, 'pagada', '2025-08-28'),
(14, 200.00, 'pagada', '2025-07-15');

-- Insertar Historial Clínico (para algunas citas atendidas)
INSERT INTO historial_clinico (id_paciente, diagnostico, tratamiento, fecha_registro) VALUES
(1, 'Hipertensión arterial', 'Seguir dieta baja en sodio y medicación diaria.', '2025-09-10'),
(2, 'Dermatitis atópica', 'Crema humectante y evitar alérgenos.', '2025-09-10'),
(6, 'Migraña crónica', 'Analgésicos y seguimiento neurológico.', '2025-08-20'),
(7, 'Fractura de cúbito', 'Inmovilización con yeso por 6 semanas.', '2025-08-22'),
(12, 'Artritis reumatoide', 'Terapia física y antiinflamatorios.', '2025-07-15');