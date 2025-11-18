-- Script SQL per creare manualmente le tabelle analytics
-- Da eseguire se non è possibile eseguire le migrations con "php spark migrate"

-- Tabella per gli eventi di analytics
CREATE TABLE IF NOT EXISTS `analytics_events` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type` VARCHAR(50) NOT NULL COMMENT 'Tipo evento: qr_scan, content_view, playback_start, etc.',
    `content_id` INT(11) UNSIGNED NULL DEFAULT NULL,
    `short_code` VARCHAR(20) NULL DEFAULT NULL,
    `session_id` VARCHAR(64) NOT NULL COMMENT 'UUID sessione utente',
    `language` VARCHAR(5) NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL COMMENT 'IPv6 compatible',
    `user_agent` TEXT NULL DEFAULT NULL,
    `device_type` VARCHAR(20) NULL DEFAULT NULL COMMENT 'mobile, tablet, desktop',
    `browser` VARCHAR(50) NULL DEFAULT NULL,
    `os` VARCHAR(50) NULL DEFAULT NULL,
    `referrer` TEXT NULL DEFAULT NULL,
    `metadata` JSON NULL DEFAULT NULL COMMENT 'Dati aggiuntivi specifici per tipo evento',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_content_id` (`content_id`),
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_analytics_events_content`
        FOREIGN KEY (`content_id`)
        REFERENCES `content` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabella per metriche aggregate per contenuto
CREATE TABLE IF NOT EXISTS `content_metrics` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` INT(11) UNSIGNED NOT NULL,
    `total_scans` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Scansioni QR totali',
    `unique_visitors` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Visitatori unici',
    `total_views` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Visualizzazioni totali',
    `playback_starts` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Riproduzioni avviate',
    `playback_completes` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Riproduzioni completate',
    `avg_completion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentuale completamento 0-100',
    `language_stats` JSON NULL DEFAULT NULL COMMENT 'Statistiche per lingua {"it": 120, "en": 45}',
    `device_stats` JSON NULL DEFAULT NULL COMMENT 'Statistiche per dispositivo {"mobile": 150, "desktop": 45}',
    `last_scan_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Ultima scansione QR',
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_content_id` (`content_id`),
    CONSTRAINT `fk_content_metrics_content`
        FOREIGN KEY (`content_id`)
        REFERENCES `content` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabella per sessioni utente
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(64) NOT NULL COMMENT 'UUID sessione',
    `first_seen` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` TEXT NULL DEFAULT NULL,
    `device_type` VARCHAR(20) NULL DEFAULT NULL,
    `total_events` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Numero totale eventi',
    `contents_viewed` JSON NULL DEFAULT NULL COMMENT 'Array di content_id visualizzati',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_session_id` (`session_id`),
    INDEX `idx_first_seen` (`first_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Verifica tabelle create
SELECT
    'analytics_events' as tabella,
    COUNT(*) as num_righe
FROM analytics_events
UNION ALL
SELECT
    'content_metrics' as tabella,
    COUNT(*) as num_righe
FROM content_metrics
UNION ALL
SELECT
    'user_sessions' as tabella,
    COUNT(*) as num_righe
FROM user_sessions;
