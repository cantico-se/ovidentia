#--------------------------------------------
#			2.2 to 2.3 
#--------------------------------------------

ALTER TABLE groups ADD manager INT (11) UNSIGNED not null AFTER vacation;
ALTER TABLE users_log ADD lastlog DATETIME not null AFTER datelog;

CREATE TABLE categoriescal (
	id TINYINT (2) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (60) not null,
	description VARCHAR (255) not null,
	bgcolor VARCHAR (6) not null,
	id_group INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);

CREATE TABLE resourcescal (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (60) not null,
	description VARCHAR (255) not null,
	id_group INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
); 

CREATE TABLE cal_events (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_cal INT (11) UNSIGNED not null,
	title VARCHAR (255) not null,
	description TEXT not null,
	start_date DATE not null,
	start_time TIME not null,
	end_date DATE not null,
	end_time TIME not null,
	id_cat INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);

CREATE TABLE calendar (
	id int(11) unsigned NOT NULL auto_increment,
	owner int(11) unsigned DEFAULT '0' NOT NULL,
	actif enum('Y','N') DEFAULT 'Y' NOT NULL,
	type TINYINT (2) not null,
	PRIMARY KEY (id)
);

INSERT INTO calendar VALUES ( '1', '1', 'Y', '1');
INSERT INTO calendar VALUES ( '2', '1', 'Y', '2');
INSERT INTO calendar VALUES ( '3', '2', 'N', '2');
INSERT INTO calendar VALUES ( '4', '3', 'Y', '2');
