CREATE DATABASE IF NOT EXISTS tournament_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tournament_db;

CREATE TABLE User (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(63) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE Tournament (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(63) NOT NULL,
    description VARCHAR(255),
    category VARCHAR(63),
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    status ENUM('upcoming', 'ongoing', 'finished') NOT NULL DEFAULT 'upcoming',
    capacity INT UNSIGNED NOT NULL,
    spots_taken INT UNSIGNED NOT NULL DEFAULT 0, 
    is_team_based BOOLEAN NOT NULL,
    creator_user_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_tournament_creator
        FOREIGN KEY (creator_user_id)
        REFERENCES User(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE 
) ENGINE=InnoDB;

CREATE TABLE Participates (
    user_id INT UNSIGNED NOT NULL,
    tournament_id INT UNSIGNED NOT NULL,
    team_name VARCHAR(63) NOT NULL,
    result_position INT UNSIGNED DEFAULT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(user_id, tournament_id),
    CONSTRAINT fk_tournament_id
        FOREIGN KEY (tournament_id)
        REFERENCES Tournament(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_user_id
        FOREIGN KEY (user_id)
        REFERENCES User(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE     
);

CREATE TABLE Matches (
  match_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT UNSIGNED NOT NULL,
  match_date DATETIME NOT NULL,
  current_round INT UNSIGNED NOT NULL, 
  side1_nickname VARCHAR(63),
  side2_nickname VARCHAR(63),
  winner VARCHAR(63) DEFAULT NULL,
  next_match_id INT UNSIGNED DEFAULT NULL,
  score VARCHAR(31) DEFAULT NULL,
  CONSTRAINT fk_match_tournament
    FOREIGN KEY (tournament_id) REFERENCES Tournament(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_match_next
    FOREIGN KEY (next_match_id) REFERENCES Matches(match_id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_winner_valid
    CHECK (winner IS NULL OR winner IN (side1_nickname, side2_nickname))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO User (username, email, password_hash, created_at)
VALUES
    ("gosho", "gosho@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("pesho", "pesho@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("tosho", "tosho@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("kris", "kris@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("rado", "rado@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("ivan", "ivan@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("stoyan", "stoyan@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("milan", "milan@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("maria", "maria@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("ani", "ani@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL),
    ("jivko", "jivko@gmail.com", "$argon2id$v=19$m=65536,t=4,p=1$RW1aaVg3YUkxcUpGdXYybw$WVep5a1eVVvLdHkyGGJXm+2qDsHI8p0xQJ2JBqvF1GY", NULL);

INSERT INTO Tournament (name, description, category, start_datetime, end_datetime, status, capacity, spots_taken, is_team_based, creator_user_id)
VALUES
  ('Шах', 'Турнир по шах с 4 участника (завършен)', 'Шах', '2026-01-01 10:00:00', '2026-01-03 18:00:00', 'finished', 4, 4, 0, 1),
  ('Футбол', 'някакво описание', 'Спорт', '2026-02-10 10:00:00', '2026-02-12 18:00:00', 'upcoming',16, 0, 1, 2),
  ('CS 2 турнир', 'някакво описание', 'Видеоигри','2026-01-01 12:00:00', '2026-03-02 18:00:00', 'ongoing',  4, 4, 0, 3);

INSERT INTO Participates (user_id, tournament_id, team_name, result_position)
VALUES
  (1, 1, 'gosho', 1),
  (2, 1, 'pesho', 3),
  (3, 1, 'tosho', 3),
  (4, 1, 'kris', 2);

INSERT INTO Matches (match_id, tournament_id, match_date, current_round, side1_nickname, side2_nickname, winner, next_match_id, score)
VALUES
  (3, 1, '2026-01-03 17:00:00', 1, 'gosho', 'kris', 'gosho', NULL, '1-0');

INSERT INTO Matches (match_id, tournament_id, match_date, current_round, side1_nickname, side2_nickname, winner, next_match_id, score)
VALUES
  (1, 1, '2026-01-02 15:00:00', 2, 'gosho', 'pesho', 'gosho', 3, '1-0'),
  (2, 1, '2026-01-02 16:00:00', 2, 'tosho', 'kris', 'kris',  3, '0-1');

INSERT INTO Participates (user_id, tournament_id, team_name)
VALUES
  (1, 3, 'gosho'),
  (2, 3, 'pesho'),
  (3, 3, 'tosho'),
  (4, 3, 'kris');

INSERT INTO Matches (match_id, tournament_id, match_date, current_round, side1_nickname, side2_nickname, winner, next_match_id, score)
VALUES
  (6, 3, '2026-01-01 12:30:00', 1, NULL, NULL, NULL, NULL, NULL),
  (4, 3, '2026-01-01 12:00:00', 2, 'gosho', 'pesho', NULL, 6, NULL),  
  (5, 3, '2026-01-01 12:00:00', 2, 'tosho', 'kris', NULL, 6, NULL);  