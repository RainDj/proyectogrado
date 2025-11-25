-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 25-11-2025 a las 00:05:17
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestion_documental`
--

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `actualizar_estado_vigencias`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_estado_vigencias` ()   BEGIN
    UPDATE documentos d
    JOIN categorias c ON d.categoria_id = c.id
    SET d.estado = 
        CASE
            -- Categorías sin vigencia: siempre vigentes
            WHEN c.vigencia_tipo = 'no_aplica' THEN 'vigente'

            -- Con vigencia en días: vencido si ya pasó
            WHEN c.vigencia_tipo = 'dias' 
                 AND d.fecha_vencimiento < CURDATE() THEN 'vencido'

            -- Próximo a vencer: faltan 10 días o menos
            WHEN c.vigencia_tipo = 'dias' 
                 AND DATEDIFF(d.fecha_vencimiento, CURDATE()) <= 10 
                 AND d.fecha_vencimiento >= CURDATE() THEN 'proximo'

            -- Resto de casos: vigente
            ELSE 'vigente'
        END;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vigencia_tipo` enum('no_aplica','dias') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_aplica',
  `vigencia_cantidad` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `vigencia_tipo`, `vigencia_cantidad`) VALUES
(1, 'RUT', 'Registro Único Tributario', 'no_aplica', NULL),
(2, 'Antecedentes fiscales', 'Documento emitido por la Contraloría', 'dias', 30),
(3, 'Antecedentes disciplinarios', 'Certificado Procuraduría', 'dias', 30),
(4, 'Antecedentes judiciales', 'Certificado Policía Nacional', 'dias', 30),
(5, 'Hoja de vida', 'Formato SECOP o HV estándar', 'no_aplica', NULL),
(6, 'Certificaciones laborales', 'Documentos de experiencia', 'no_aplica', NULL),
(7, 'Paz y salvo municipal', 'Certificado de paz y salvo de impuestos municipales', 'dias', 30),
(8, 'Certificado no inhabilidades', 'Certificado de no estar inhabilitado para contratar', 'dias', 30),
(9, 'Certificado delitos sexuales', 'Antecedentes por delitos sexuales', 'dias', 30),
(10, 'Tarjeta profesional', 'Tarjeta profesional del contratista', 'no_aplica', NULL),
(11, 'Libreta militar', 'Libreta militar del contratista', 'no_aplica', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos`
--

DROP TABLE IF EXISTS `documentos`;
CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `categoria_id` int NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('vigente','proximo','vencido') COLLATE utf8mb4_unicode_ci DEFAULT 'vigente',
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `categoria_id` (`categoria_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `procesos_documentales`
--

DROP TABLE IF EXISTS `procesos_documentales`;
CREATE TABLE IF NOT EXISTS `procesos_documentales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `entidad` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','incompleto','completo') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `procesos_documentales`
--

INSERT INTO `procesos_documentales` (`id`, `usuario_id`, `entidad`, `descripcion`, `fecha_creacion`, `estado`) VALUES
(1, 1, 'Entidad Ejemplo', 'Ejemplo', '2025-11-24 19:57:25', 'incompleto');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proceso_items`
--

DROP TABLE IF EXISTS `proceso_items`;
CREATE TABLE IF NOT EXISTS `proceso_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proceso_id` int NOT NULL,
  `categoria_id` int NOT NULL,
  `documento_id` int DEFAULT NULL,
  `estado` enum('faltante','vigente','vencido') COLLATE utf8mb4_unicode_ci DEFAULT 'faltante',
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proceso_id` (`proceso_id`),
  KEY `categoria_id` (`categoria_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proceso_items`
--

INSERT INTO `proceso_items` (`id`, `proceso_id`, `categoria_id`, `documento_id`, `estado`, `observacion`) VALUES
(1, 1, 3, 3, 'vigente', NULL),
(2, 1, 2, NULL, 'faltante', NULL),
(3, 1, 4, 4, 'vencido', NULL),
(4, 1, 6, NULL, 'faltante', NULL),
(5, 1, 9, NULL, 'faltante', NULL),
(6, 1, 8, NULL, 'faltante', NULL),
(7, 1, 5, 1, 'vigente', NULL),
(8, 1, 11, NULL, 'faltante', NULL),
(9, 1, 7, NULL, 'faltante', NULL),
(10, 1, 1, NULL, 'faltante', NULL),
(11, 1, 10, NULL, 'faltante', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','usuario') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usuario',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `fecha_registro`) VALUES
(1, 'Contratista Pepito', 'test@test.com', '$2y$10$mtJ3LfBrpOoYBqIdi/as2uKhLif4x/fdHdg7D5vi3yDtkWs5uuaNu', 'usuario', '2025-11-18 21:47:24'),
(2, 'Nelson Leal', 'admin@admin.com', '$2y$10$z4Bh9AMNhFw5ZtYpyrXZ8u9enCUhKJUrkDOSZnb1hKs56TfC/1BCa', 'admin', '2025-11-24 21:35:18');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
