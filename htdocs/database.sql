-- ============================================================
-- equiz — database.sql
-- Import this file from phpMyAdmin (InfinityFree) to create
-- all tables (empty — questions are created from admin.php).
-- Compatible with MySQL / MariaDB.
-- ============================================================

CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_text` VARCHAR(500) NOT NULL,
  `option_a` VARCHAR(255) NOT NULL,
  `option_b` VARCHAR(255) NOT NULL,
  `option_c` VARCHAR(255) NOT NULL,
  `option_d` VARCHAR(255) NOT NULL,
  `correct_option` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=A 1=B 2=C 3=D',
  `time_limit` INT UNSIGNED NOT NULL DEFAULT 20,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active_order` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `games` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pin` VARCHAR(10) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'lobby' COMMENT 'lobby|countdown|question|review|leaderboard|finished',
  `current_order` INT NOT NULL DEFAULT 0 COMMENT '1-based index of current question, 0 = none yet',
  `total_questions` INT NOT NULL DEFAULT 0,
  `current_started_at` INT UNSIGNED NULL DEFAULT NULL COMMENT 'unix timestamp (server time)',
  `current_ends_at` INT UNSIGNED NULL DEFAULT NULL COMMENT 'unix timestamp (server time)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pin` (`pin`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `game_questions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT UNSIGNED NOT NULL,
  `q_order` INT NOT NULL COMMENT '1-based order inside this game',
  `question_text` VARCHAR(500) NOT NULL,
  `option_a` VARCHAR(255) NOT NULL,
  `option_b` VARCHAR(255) NOT NULL,
  `option_c` VARCHAR(255) NOT NULL,
  `option_d` VARCHAR(255) NOT NULL,
  `correct_option` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `time_limit` INT UNSIGNED NOT NULL DEFAULT 20,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_game_order` (`game_id`, `q_order`),
  KEY `idx_game` (`game_id`),
  CONSTRAINT `fk_gq_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `players` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(30) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `score` INT NOT NULL DEFAULT 0 COMMENT 'speed points, tiebreak only',
  `lives` INT NOT NULL DEFAULT 5 COMMENT 'battle royale lives',
  `correct_count` INT NOT NULL DEFAULT 0,
  `last_seen` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  UNIQUE KEY `uq_game_name` (`game_id`, `name`),
  KEY `idx_game_score` (`game_id`, `score`),
  CONSTRAINT `fk_players_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `answers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT UNSIGNED NOT NULL,
  `game_question_id` INT UNSIGNED NOT NULL,
  `player_id` INT UNSIGNED NOT NULL,
  `selected_option` TINYINT UNSIGNED NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `points` INT NOT NULL DEFAULT 0,
  `response_ms` INT UNSIGNED NOT NULL DEFAULT 0,
  `answered_at` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_q_player` (`game_question_id`, `player_id`),
  KEY `idx_game_q` (`game_id`, `game_question_id`),
  CONSTRAINT `fk_answers_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_gq` FOREIGN KEY (`game_question_id`) REFERENCES `game_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No sample questions included on purpose: the teacher creates all
-- questions from admin.php (new / edit / delete / activate).
