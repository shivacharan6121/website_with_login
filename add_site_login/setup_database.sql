CREATE DATABASE IF NOT EXISTS connector_management;
USE connector_management;

CREATE TABLE IF NOT EXISTS parts (
    part_no VARCHAR(50) PRIMARY KEY,
    quantity INT NOT NULL DEFAULT 0
);
