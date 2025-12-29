CREATE DATABASE IF NOT EXISTS tournament_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tournament_db;

CREATE TABLE User (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(63) NOT NULL UNIQUE,
    email VARCHAR(63) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE Tournament (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(63) NOT NULL,
    description VARCHAR(255),
    category VARCHAR(31),
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
    result_position INT UNSIGNED,
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