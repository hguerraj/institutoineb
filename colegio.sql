-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-10-2024 a las 18:24:11
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
-- Base de datos: `colegio`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `Fecha_Nacimiento` date DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Grado_ID` int(11) DEFAULT NULL,
  `Fecha_Registro` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `ID` int(11) NOT NULL,
  `Nombre_Carrera` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carreras`
--

INSERT INTO `carreras` (`ID`, `Nombre_Carrera`, `descripcion`) VALUES
(2, 'Bachillerato en Ciencias y Letras', 'Carrera que proporciona una formación general en ciencias y humanidades, preparándose para continuar estudios universitarios en diversas áreas.'),
(3, 'Perito Contador', 'Enfocada en la formación de técnicos en contabilidad, administración financiera y fiscal, capacitados para trabajar en empresas y negocios.'),
(4, 'Bachillerato en Ciencias y Letras con Orientación en Computación', 'Además de los estudios generales, esta carrera ofrece conocimientos específicos en informática y programación, preparando al estudiante para carreras tecnológicas.'),
(5, 'Perito en Administración de Empresas', 'Orientada a la gestión empresarial, esta carrera enseña conceptos de administración, finanzas y mercadeo, preparando al estudiante para roles administrativos.'),
(6, 'Perito en Electrónica', 'Formación técnica en el diseño, mantenimiento y reparación de sistemas electrónicos, preparándolos para trabajar en industrias tecnológicas y de telecomunicaciones.'),
(7, 'Perito en Dibujo Técnico', 'Carrera que enseña técnicas de dibujo técnico aplicado a la construcción, ingeniería y arquitectura, capacitando a los estudiantes para trabajar en diseño y elaboración de planos.'),
(8, 'Secretariado Bilingüe', 'Carrera que prepara al estudiante en habilidades administrativas, con un enfoque en la comunicación en inglés y la gestión de oficina.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `ID` int(11) NOT NULL,
  `Nombre_Curso` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `Grado_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grados`
--

CREATE TABLE `grados` (
  `ID` int(11) NOT NULL,
  `Nombre_Grado` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `Carrera_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grados_secciones`
--

CREATE TABLE `grados_secciones` (
  `ID` int(11) NOT NULL,
  `Nombre_Grado` varchar(50) DEFAULT NULL,
  `nombre_seccion` varchar(255) DEFAULT NULL,
  `Nivel_Educativo` varchar(50) DEFAULT NULL,
  `Grado_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `ID` int(11) NOT NULL,
  `Alumno_ID` int(11) DEFAULT NULL,
  `Grado_ID` int(11) DEFAULT NULL,
  `fecha_inscripcion` date DEFAULT NULL,
  `monto_inscripcion` decimal(10,2) DEFAULT NULL,
  `Estado_Pago` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas`
--

CREATE TABLE `notas` (
  `ID` int(11) NOT NULL,
  `Alumno_ID` int(11) DEFAULT NULL,
  `Unidad_ID` int(11) DEFAULT NULL,
  `calificacion` decimal(5,2) DEFAULT NULL,
  `Periodo_Academico` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padres_encargados`
--

CREATE TABLE `padres_encargados` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `relacion_alumno` varchar(255) DEFAULT NULL,
  `Alumno_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `ID` int(11) NOT NULL,
  `Alumno_ID` int(11) DEFAULT NULL,
  `Tipo_Pago` varchar(50) DEFAULT NULL,
  `Monto` decimal(10,2) DEFAULT NULL,
  `Fecha_Pago` date DEFAULT NULL,
  `anio` year(4) DEFAULT NULL,
  `Mes` int(11) DEFAULT NULL,
  `Estado_Pago` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `phpcg_users`
--

CREATE TABLE `phpcg_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `profiles_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `address` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL COMMENT 'Boolean'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `phpcg_users`
--

INSERT INTO `phpcg_users` (`id`, `profiles_id`, `name`, `firstname`, `address`, `city`, `zip_code`, `email`, `phone`, `mobile_phone`, `password`, `active`) VALUES
(1, 1, 'Guerra', 'Hector', 'Zaragocha', 'Guatemala', '04006', 'hectoralfredogj@gmail.com', '11111111', '22222222', '$2y$10$fdG0Xje6H7ASXvWPGzDLHO4DXcjome9IeeVm5CThckodSoTjMtLa.', 1),
(2, 2, 'Nohemi', 'Maestra', 'Zaragocha', 'Guatemala', '04006', 'nohemi@gmail.com', '33333333', '44444444', '$2y$10$jqDXT2jMNkIpPPc/Wz0ELO9LSikgloo6Ol.GD0Uj6YgDqVKqLlVtm', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `phpcg_users_profiles`
--

CREATE TABLE `phpcg_users_profiles` (
  `id` int(11) NOT NULL,
  `profile_name` varchar(100) NOT NULL,
  `r_alumnos` tinyint(1) NOT NULL DEFAULT 0,
  `u_alumnos` tinyint(1) NOT NULL DEFAULT 0,
  `cd_alumnos` tinyint(1) NOT NULL DEFAULT 0,
  `cq_alumnos` varchar(255) DEFAULT NULL,
  `r_carreras` tinyint(1) NOT NULL DEFAULT 0,
  `u_carreras` tinyint(1) NOT NULL DEFAULT 0,
  `cd_carreras` tinyint(1) NOT NULL DEFAULT 0,
  `cq_carreras` varchar(255) DEFAULT NULL,
  `r_cursos` tinyint(1) NOT NULL DEFAULT 0,
  `u_cursos` tinyint(1) NOT NULL DEFAULT 0,
  `cd_cursos` tinyint(1) NOT NULL DEFAULT 0,
  `cq_cursos` varchar(255) DEFAULT NULL,
  `r_grados` tinyint(1) NOT NULL DEFAULT 0,
  `u_grados` tinyint(1) NOT NULL DEFAULT 0,
  `cd_grados` tinyint(1) NOT NULL DEFAULT 0,
  `cq_grados` varchar(255) DEFAULT NULL,
  `r_grados_secciones` tinyint(1) NOT NULL DEFAULT 0,
  `u_grados_secciones` tinyint(1) NOT NULL DEFAULT 0,
  `cd_grados_secciones` tinyint(1) NOT NULL DEFAULT 0,
  `cq_grados_secciones` varchar(255) DEFAULT NULL,
  `r_inscripciones` tinyint(1) NOT NULL DEFAULT 0,
  `u_inscripciones` tinyint(1) NOT NULL DEFAULT 0,
  `cd_inscripciones` tinyint(1) NOT NULL DEFAULT 0,
  `cq_inscripciones` varchar(255) DEFAULT NULL,
  `r_notas` tinyint(1) NOT NULL DEFAULT 0,
  `u_notas` tinyint(1) NOT NULL DEFAULT 0,
  `cd_notas` tinyint(1) NOT NULL DEFAULT 0,
  `cq_notas` varchar(255) DEFAULT NULL,
  `r_padres_encargados` tinyint(1) NOT NULL DEFAULT 0,
  `u_padres_encargados` tinyint(1) NOT NULL DEFAULT 0,
  `cd_padres_encargados` tinyint(1) NOT NULL DEFAULT 0,
  `cq_padres_encargados` varchar(255) DEFAULT NULL,
  `r_pagos` tinyint(1) NOT NULL DEFAULT 0,
  `u_pagos` tinyint(1) NOT NULL DEFAULT 0,
  `cd_pagos` tinyint(1) NOT NULL DEFAULT 0,
  `cq_pagos` varchar(255) DEFAULT NULL,
  `r_profesores` tinyint(1) NOT NULL DEFAULT 0,
  `u_profesores` tinyint(1) NOT NULL DEFAULT 0,
  `cd_profesores` tinyint(1) NOT NULL DEFAULT 0,
  `cq_profesores` varchar(255) DEFAULT NULL,
  `r_unidades` tinyint(1) NOT NULL DEFAULT 0,
  `u_unidades` tinyint(1) NOT NULL DEFAULT 0,
  `cd_unidades` tinyint(1) NOT NULL DEFAULT 0,
  `cq_unidades` varchar(255) DEFAULT NULL,
  `r_phpcg_users` tinyint(1) NOT NULL DEFAULT 0,
  `u_phpcg_users` tinyint(1) NOT NULL DEFAULT 0,
  `cd_phpcg_users` tinyint(1) NOT NULL DEFAULT 0,
  `cq_phpcg_users` varchar(255) DEFAULT NULL,
  `r_phpcg_users_profiles` tinyint(1) NOT NULL DEFAULT 0,
  `u_phpcg_users_profiles` tinyint(1) NOT NULL DEFAULT 0,
  `cd_phpcg_users_profiles` tinyint(1) NOT NULL DEFAULT 0,
  `cq_phpcg_users_profiles` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `phpcg_users_profiles`
--

INSERT INTO `phpcg_users_profiles` (`id`, `profile_name`, `r_alumnos`, `u_alumnos`, `cd_alumnos`, `cq_alumnos`, `r_carreras`, `u_carreras`, `cd_carreras`, `cq_carreras`, `r_cursos`, `u_cursos`, `cd_cursos`, `cq_cursos`, `r_grados`, `u_grados`, `cd_grados`, `cq_grados`, `r_grados_secciones`, `u_grados_secciones`, `cd_grados_secciones`, `cq_grados_secciones`, `r_inscripciones`, `u_inscripciones`, `cd_inscripciones`, `cq_inscripciones`, `r_notas`, `u_notas`, `cd_notas`, `cq_notas`, `r_padres_encargados`, `u_padres_encargados`, `cd_padres_encargados`, `cq_padres_encargados`, `r_pagos`, `u_pagos`, `cd_pagos`, `cq_pagos`, `r_profesores`, `u_profesores`, `cd_profesores`, `cq_profesores`, `r_unidades`, `u_unidades`, `cd_unidades`, `cq_unidades`, `r_phpcg_users`, `u_phpcg_users`, `cd_phpcg_users`, `cq_phpcg_users`, `r_phpcg_users_profiles`, `u_phpcg_users_profiles`, `cd_phpcg_users_profiles`, `cq_phpcg_users_profiles`) VALUES
(1, 'superadmin', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, '', 2, 2, 2, ''),
(2, 'Maestros', 2, 0, 0, '', 2, 0, 0, '', 2, 0, 0, '', 2, 0, 0, '', 2, 0, 0, '', 0, 0, 0, '', 2, 2, 2, '', 0, 0, 0, '', 0, 0, 0, '', 0, 0, 0, '', 2, 0, 0, '', 0, 0, 0, '', 0, 0, 0, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `Fecha_Nacimiento` date DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Asignaturas` text DEFAULT NULL,
  `Horario` varchar(255) DEFAULT NULL,
  `Grado_ID` int(11) DEFAULT NULL,
  `Usuario_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `ID` int(11) NOT NULL,
  `Curso_ID` int(11) DEFAULT NULL,
  `Nombre_Unidad` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `numero_unidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Alumnos_Grados` (`Grado_ID`);

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Cursos_Grados` (`Grado_ID`);

--
-- Indices de la tabla `grados`
--
ALTER TABLE `grados`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Grados_Carreras` (`Carrera_ID`);

--
-- Indices de la tabla `grados_secciones`
--
ALTER TABLE `grados_secciones`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Grados_Secciones_Grados` (`Grado_ID`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Inscripciones_Alumnos` (`Alumno_ID`),
  ADD KEY `FK_Inscripciones_Grados` (`Grado_ID`);

--
-- Indices de la tabla `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Notas_Alumnos` (`Alumno_ID`),
  ADD KEY `FK_Notas_Unidades` (`Unidad_ID`);

--
-- Indices de la tabla `padres_encargados`
--
ALTER TABLE `padres_encargados`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Padres_Encargados_Alumnos` (`Alumno_ID`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Pagos_Alumnos` (`Alumno_ID`);

--
-- Indices de la tabla `phpcg_users`
--
ALTER TABLE `phpcg_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_UNIQUE` (`email`),
  ADD KEY `fk_phpcg_users_phpcg_users_profiles` (`profiles_id`);

--
-- Indices de la tabla `phpcg_users_profiles`
--
ALTER TABLE `phpcg_users_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `profile_name_UNIQUE` (`profile_name`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Profesores_Grados` (`Grado_ID`),
  ADD KEY `FK_Profesores_Usuarios` (`Usuario_ID`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_Unidades_Cursos` (`Curso_ID`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grados`
--
ALTER TABLE `grados`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `grados_secciones`
--
ALTER TABLE `grados_secciones`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `notas`
--
ALTER TABLE `notas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `padres_encargados`
--
ALTER TABLE `padres_encargados`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `phpcg_users`
--
ALTER TABLE `phpcg_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `phpcg_users_profiles`
--
ALTER TABLE `phpcg_users_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD CONSTRAINT `FK_Alumnos_Grados` FOREIGN KEY (`Grado_ID`) REFERENCES `grados` (`ID`);

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `FK_Cursos_Grados` FOREIGN KEY (`Grado_ID`) REFERENCES `grados` (`ID`);

--
-- Filtros para la tabla `grados`
--
ALTER TABLE `grados`
  ADD CONSTRAINT `FK_Grados_Carreras` FOREIGN KEY (`Carrera_ID`) REFERENCES `carreras` (`ID`);

--
-- Filtros para la tabla `grados_secciones`
--
ALTER TABLE `grados_secciones`
  ADD CONSTRAINT `FK_Grados_Secciones_Grados` FOREIGN KEY (`Grado_ID`) REFERENCES `grados` (`ID`);

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `FK_Inscripciones_Alumnos` FOREIGN KEY (`Alumno_ID`) REFERENCES `alumnos` (`ID`),
  ADD CONSTRAINT `FK_Inscripciones_Grados` FOREIGN KEY (`Grado_ID`) REFERENCES `grados` (`ID`);

--
-- Filtros para la tabla `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `FK_Notas_Alumnos` FOREIGN KEY (`Alumno_ID`) REFERENCES `alumnos` (`ID`),
  ADD CONSTRAINT `FK_Notas_Unidades` FOREIGN KEY (`Unidad_ID`) REFERENCES `unidades` (`ID`);

--
-- Filtros para la tabla `padres_encargados`
--
ALTER TABLE `padres_encargados`
  ADD CONSTRAINT `FK_Padres_Encargados_Alumnos` FOREIGN KEY (`Alumno_ID`) REFERENCES `alumnos` (`ID`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `FK_Pagos_Alumnos` FOREIGN KEY (`Alumno_ID`) REFERENCES `alumnos` (`ID`);

--
-- Filtros para la tabla `phpcg_users`
--
ALTER TABLE `phpcg_users`
  ADD CONSTRAINT `fk_phpcg_users_phpcg_users_profiles` FOREIGN KEY (`profiles_id`) REFERENCES `phpcg_users_profiles` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD CONSTRAINT `FK_Profesores_Grados` FOREIGN KEY (`Grado_ID`) REFERENCES `grados` (`ID`);

--
-- Filtros para la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD CONSTRAINT `FK_Unidades_Cursos` FOREIGN KEY (`Curso_ID`) REFERENCES `cursos` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
