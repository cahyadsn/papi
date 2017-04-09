DROP TABLE IF EXISTS papi_aspects;
CREATE TABLE IF NOT EXISTS papi_aspects(
	id TINYINT PRIMARY KEY,
	aspect VARCHAR(50)
);
DROP TABLE IF EXISTS papi_roles;
CREATE TABLE IF NOT EXISTS papi_roles(
	id TINYINT PRIMARY KEY,
	aspect_id TINYINT,
	code CHAR(1),
	role VARCHAR(50)
);
DROP TABLE IF EXISTS papi_questions;
CREATE TABLE IF NOT EXISTS papi_questions(
	id TINYINT PRIMARY KEY,
	question1 VARCHAR(75),
	value1 TINYINT,
	question2 VARCHAR(75),
	value2 TINYINT
);
DROP TABLE IF EXISTS papi_rules;
CREATE TABLE IF NOT EXISTS papi_rules(
	id TINYINT AUTO_INCREMENT PRIMARY KEY,
	role_id TINYINT,
	low_value TINYINT,
	high_value TINYINT,
	interprestation VARCHAR(255)
);
DROP TABLE IF EXISTS papi_users;
CREATE TABLE IF NOT EXISTS papi_users(
	id TINYINT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(30),
	fullname VARCHAR(30)
);
DROP TABLE IF EXISTS papi_results;
CREATE TABLE IF NOT EXISTS papi_results(
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id TINYINT,
	role_id TINYINT,
	value TINYINT
);