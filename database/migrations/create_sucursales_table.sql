-- Migration: Add sucursales (branches) table for multi-location geolocated login
-- Date: 2026-09-02
--
-- Reemplaza la ubicacion unica de geo_login_* (guardada en global_config) por
-- una lista de sucursales, cada una con su propia direccion/lat/lng/radio.
-- El login geolocalizado para asesores ahora es valido si el usuario esta
-- dentro del radio de CUALQUIER sucursal activa.
--
-- Aplicar manualmente (phpMyAdmin, mysql CLI, etc). No hay runner automatico
-- de migraciones en este proyecto (ver database/migrations/README.md).

CREATE TABLE IF NOT EXISTS `sucursales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `radio_metros` int(11) NOT NULL DEFAULT 100,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migra la ubicacion unica existente (si estaba configurada con lat/lng validos)
-- como la primera sucursal, para que el login geolocalizado siga funcionando
-- inmediatamente despues de aplicar esta migracion.
INSERT INTO `sucursales` (`nombre`, `direccion`, `latitud`, `longitud`, `radio_metros`, `activo`)
SELECT
    'Sucursal Principal',
    (SELECT config_value FROM global_config WHERE config_key = 'geo_login_address'),
    (SELECT config_value FROM global_config WHERE config_key = 'geo_login_latitude'),
    (SELECT config_value FROM global_config WHERE config_key = 'geo_login_longitude'),
    COALESCE((SELECT config_value FROM global_config WHERE config_key = 'geo_login_radius_meters'), 100),
    1
WHERE EXISTS (
    SELECT 1 FROM global_config
    WHERE config_key = 'geo_login_latitude' AND config_value REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
)
AND EXISTS (
    SELECT 1 FROM global_config
    WHERE config_key = 'geo_login_longitude' AND config_value REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
)
AND NOT EXISTS (SELECT 1 FROM `sucursales`);