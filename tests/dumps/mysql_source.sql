-- =============================================================================
-- Clonio Test Dump: MySQL Source Database
-- Usage: mysql -u root -p clonio_test_source < tests/dumps/mysql_source.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS clonio_test_source CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clonio_test_source;

-- -----------------------------------------------------------------------------
-- Schema
-- NOTE: No unique constraint on email (intentional — mirrors real-world MySQL
--       apps that were seeded without strict constraints).
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS post_tag;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255)    NOT NULL,
    email      VARCHAR(255)    NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password   VARCHAR(255)    NOT NULL,
    status     ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP       NULL DEFAULT NULL,
    updated_at TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)    NOT NULL,
    created_at TIMESTAMP       NULL DEFAULT NULL,
    updated_at TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY tags_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED  NOT NULL,
    title        VARCHAR(255)  NOT NULL,
    slug         VARCHAR(255)  NOT NULL,
    body         TEXT          NOT NULL,
    published_at TIMESTAMP     NULL DEFAULT NULL,
    created_at   TIMESTAMP     NULL DEFAULT NULL,
    updated_at   TIMESTAMP     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY posts_slug_unique (slug),
    KEY posts_user_id_foreign (user_id),
    CONSTRAINT posts_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_tag (
    post_id INT UNSIGNED NOT NULL,
    tag_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    KEY post_tag_tag_id_foreign (tag_id),
    CONSTRAINT post_tag_post_id_foreign FOREIGN KEY (post_id) REFERENCES posts (id),
    CONSTRAINT post_tag_tag_id_foreign  FOREIGN KEY (tag_id)  REFERENCES tags (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Data
-- -----------------------------------------------------------------------------

INSERT INTO users (id, name, email, email_verified_at, password, status, created_at, updated_at) VALUES
(1,  'Alice Müller',    'alice@example.com',   '2024-01-01 08:00:00', 'hashed_pw_1',  'active',    '2024-01-01 08:00:00', '2024-01-01 08:00:00'),
(2,  'Bob Schmidt',     'bob@example.com',     '2024-01-02 09:00:00', 'hashed_pw_2',  'active',    '2024-01-02 09:00:00', '2024-01-02 09:00:00'),
(3,  'Clara Weber',     'clara@example.com',   NULL,                  'hashed_pw_3',  'pending',   '2024-01-03 10:00:00', '2024-01-03 10:00:00'),
(4,  'David Fischer',   'david@example.com',   '2024-01-04 11:00:00', 'hashed_pw_4',  'active',    '2024-01-04 11:00:00', '2024-01-04 11:00:00'),
(5,  'Eva Bauer',       'eva@example.com',     '2024-01-05 12:00:00', 'hashed_pw_5',  'active',    '2024-01-05 12:00:00', '2024-01-05 12:00:00'),
(6,  'Frank Richter',   'frank@example.com',   NULL,                  'hashed_pw_6',  'suspended', '2024-01-06 13:00:00', '2024-01-06 13:00:00'),
(7,  'Greta Köhler',    'greta@example.com',   '2024-01-07 14:00:00', 'hashed_pw_7',  'active',    '2024-01-07 14:00:00', '2024-01-07 14:00:00'),
(8,  'Hans Wolf',       'hans@example.com',     '2024-01-08 15:00:00', 'hashed_pw_8',  'active',    '2024-01-08 15:00:00', '2024-01-08 15:00:00'),
(9,  'Irene Schröder',  'irene@example.com',   NULL,                  'hashed_pw_9',  'pending',   '2024-01-09 16:00:00', '2024-01-09 16:00:00'),
(10, 'Jan Braun',       'jan@example.com',     '2024-01-10 17:00:00', 'hashed_pw_10', 'active',    '2024-01-10 17:00:00', '2024-01-10 17:00:00');

INSERT INTO tags (id, name, created_at, updated_at) VALUES
(1, 'Laravel',     '2024-01-01 08:00:00', '2024-01-01 08:00:00'),
(2, 'PHP',         '2024-01-01 08:00:00', '2024-01-01 08:00:00'),
(3, 'PostgreSQL',  '2024-01-01 08:00:00', '2024-01-01 08:00:00'),
(4, 'MySQL',       '2024-01-01 08:00:00', '2024-01-01 08:00:00'),
(5, 'Deployment',  '2024-01-01 08:00:00', '2024-01-01 08:00:00');

INSERT INTO posts (id, user_id, title, slug, body, published_at, created_at, updated_at) VALUES
(1,  1, 'Getting started with Laravel',     'getting-started-laravel',     'Laravel is a web application framework...', '2024-02-01 10:00:00', '2024-02-01 09:00:00', '2024-02-01 09:00:00'),
(2,  1, 'Eloquent ORM deep dive',           'eloquent-orm-deep-dive',       'Eloquent provides a beautiful ORM...',      '2024-02-05 10:00:00', '2024-02-05 09:00:00', '2024-02-05 09:00:00'),
(3,  2, 'MySQL performance tuning',         'mysql-performance-tuning',     'When your queries slow down...',            '2024-02-10 10:00:00', '2024-02-10 09:00:00', '2024-02-10 09:00:00'),
(4,  2, 'Indexing strategies',              'indexing-strategies',          'Proper indexing is key to performance...', '2024-02-12 10:00:00', '2024-02-12 09:00:00', '2024-02-12 09:00:00'),
(5,  3, 'Deploying with Docker',            'deploying-with-docker',        'Containerization made easy...',             '2024-02-15 10:00:00', '2024-02-15 09:00:00', '2024-02-15 09:00:00'),
(6,  4, 'PostgreSQL vs MySQL',              'postgresql-vs-mysql',          'Both are excellent databases...',           '2024-02-18 10:00:00', '2024-02-18 09:00:00', '2024-02-18 09:00:00'),
(7,  5, 'PHP 8.4 new features',             'php-84-new-features',          'PHP 8.4 brings many improvements...',       '2024-02-20 10:00:00', '2024-02-20 09:00:00', '2024-02-20 09:00:00'),
(8,  6, 'Laravel queues explained',         'laravel-queues-explained',     'Queue workers process jobs...',             NULL,                  '2024-02-22 09:00:00', '2024-02-22 09:00:00'),
(9,  7, 'Database migrations best practices', 'db-migrations-best-practices', 'Migrations should be reversible...',    '2024-02-25 10:00:00', '2024-02-25 09:00:00', '2024-02-25 09:00:00'),
(10, 8, 'API design with Laravel',          'api-design-laravel',           'RESTful APIs in Laravel...',                '2024-03-01 10:00:00', '2024-03-01 09:00:00', '2024-03-01 09:00:00'),
(11, 9, 'Testing with Pest',                'testing-with-pest',            'Pest makes testing enjoyable...',           '2024-03-05 10:00:00', '2024-03-05 09:00:00', '2024-03-05 09:00:00'),
(12, 10, 'CI/CD pipelines for PHP',         'cicd-pipelines-php',           'Automate your deployments...',              '2024-03-08 10:00:00', '2024-03-08 09:00:00', '2024-03-08 09:00:00'),
(13, 1, 'Advanced Blade templating',        'advanced-blade-templating',    'Blade components unlock power...',          '2024-03-10 10:00:00', '2024-03-10 09:00:00', '2024-03-10 09:00:00'),
(14, 3, 'Event sourcing in PHP',            'event-sourcing-php',           'Event sourcing captures state...',          NULL,                  '2024-03-12 09:00:00', '2024-03-12 09:00:00'),
(15, 5, 'Scaling Laravel applications',     'scaling-laravel',              'When your app grows...',                    '2024-03-15 10:00:00', '2024-03-15 09:00:00', '2024-03-15 09:00:00');

INSERT INTO post_tag (post_id, tag_id) VALUES
(1,  1), (1,  2),
(2,  1), (2,  2),
(3,  4), (3,  2),
(4,  4),
(5,  5),
(6,  3), (6,  4),
(7,  2),
(8,  1),
(9,  4), (9,  3),
(10, 1), (10, 2),
(11, 1), (11, 2),
(12, 5), (12, 2),
(13, 1),
(14, 2),
(15, 1), (15, 5);
