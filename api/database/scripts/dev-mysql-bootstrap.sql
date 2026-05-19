-- Dev bootstrap: central DB + app user (run as MySQL admin / baldurt1992)
CREATE DATABASE IF NOT EXISTS freelance_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'freelance' @'localhost' IDENTIFIED BY 'freelance_dev_secret';
GRANT ALL PRIVILEGES ON freelance_central.* TO 'freelance' @'localhost';
-- Stancl creates DBs named tenant{uuid}
GRANT ALL PRIVILEGES ON `tenant%`.* TO 'freelance' @'localhost';
GRANT CREATE ON *.* TO 'freelance' @'localhost';
FLUSH PRIVILEGES;