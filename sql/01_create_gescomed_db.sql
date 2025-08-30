-- Practica de Taller de Bases de Datos
-- Script para la creación de la estructura de la base de datos GESCOMED

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS gescomed;
USE gescomed;

-- Tabla: paciente
CREATE TABLE paciente (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(15),
    direccion VARCHAR(100),
    email VARCHAR(100) UNIQUE
);

-- Tabla: especialidad
CREATE TABLE especialidad (
    id_especialidad INT AUTO_INCREMENT PRIMARY KEY,
    nombre_especialidad VARCHAR(100) NOT NULL
);

-- Tabla: consultorio
CREATE TABLE consultorio (
    id_consultorio INT AUTO_INCREMENT PRIMARY KEY,
    nombre_consultorio VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(150)
);

-- Tabla: medico
CREATE TABLE medico (
    id_medico INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    telefono VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    id_especialidad INT NOT NULL,
    id_consultorio INT NOT NULL,
    FOREIGN KEY (id_especialidad) REFERENCES especialidad(id_especialidad),
    FOREIGN KEY (id_consultorio) REFERENCES consultorio(id_consultorio)
);

-- Tabla: cita
CREATE TABLE cita (
    id_cita INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_medico INT NOT NULL,
    fecha_cita DATE NOT NULL,
    hora_cita TIME NOT NULL,
    estado ENUM('pendiente','atendida','cancelada') DEFAULT 'pendiente',
    FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente),
    FOREIGN KEY (id_medico) REFERENCES medico(id_medico)
);

-- Tabla: historial_clinico
CREATE TABLE historial_clinico (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    diagnostico TEXT NOT NULL,
    tratamiento TEXT,
    fecha_registro DATE NOT NULL,
    FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente)
);

-- Tabla: factura
CREATE TABLE factura (
    id_factura INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    estado_pago ENUM('pendiente','pagada') DEFAULT 'pendiente',
    fecha_emision DATE NOT NULL,
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita)
);