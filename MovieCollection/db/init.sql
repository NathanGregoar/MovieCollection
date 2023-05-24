CREATE DATABASE IF NOT EXISTS movie_collection;

USE movie_collection;

CREATE TABLE IF NOT EXISTS films (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  director VARCHAR(255) DEFAULT '/',
  release_year INT DEFAULT 0,
  external_hard_drive VARCHAR(255) DEFAULT NULL
);

GRANT ALL PRIVILEGES ON movie_collection.* TO 'nathan'@'%' IDENTIFIED BY '444719';
FLUSH PRIVILEGES;
