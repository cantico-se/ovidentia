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

CREATE TABLE calaccess_users (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_cal INT (11) UNSIGNED not null,
	id_user INT (11) UNSIGNED not null,
	bwrite enum('N','Y') DEFAULT 'N' NOT NULL,
	PRIMARY KEY (id)
);

#--------------------------------------------
#			2.3 to 2.4 
#--------------------------------------------

ALTER TABLE users ADD lang VARCHAR (10) not null;

CREATE TABLE caloptions (
	id INT (11) UNSIGNED not null AUTO_INCREMENT, 
	id_user INT (11) UNSIGNED not null, 
	startday TINYINT DEFAULT '0' not null, 
	allday ENUM ('Y','N') not null, 
	viewcat ENUM ('Y','N') not null, 
	usebgcolor ENUM ('Y','N') not null, 
	PRIMARY KEY (id)
);

ALTER TABLE groups ADD mail ENUM ('N','Y') not null AFTER vacation;

CREATE TABLE mailview_groups (
	id int(11) unsigned NOT NULL auto_increment,
	id_object int(11) unsigned DEFAULT '0' NOT NULL,
	id_group int(11) unsigned DEFAULT '0' NOT NULL,
	UNIQUE id (id)
); 

CREATE TABLE mail_domains (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (254) not null,
	description VARCHAR (224) not null,
	outserver VARCHAR (224) not null,
	inserver VARCHAR (224) not null,
	outport VARCHAR (5) not null,
	inport VARCHAR (5) not null,
	access VARCHAR (10) not null,
	bgroup enum('N','Y') DEFAULT 'N' NOT NULL,
	owner INT (11) UNSIGNED not null,	
	PRIMARY KEY (id)
);

CREATE TABLE mail_accounts (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (255) not null,
	email VARCHAR (255) not null,
	account VARCHAR (255) not null,
	password VARCHAR (255) not null,
	domain INT (11) UNSIGNED not null,
	owner INT (11) UNSIGNED not null,
	maxrows TINYINT (2) not null,
	prefered enum('N','Y') DEFAULT 'N' NOT NULL,
	format VARCHAR (5) DEFAULT 'plain' NOT NULL,
	PRIMARY KEY (id)
);

CREATE TABLE mail_signatures (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	owner INT (11) UNSIGNED not null,
	html enum('Y','N') DEFAULT 'N' NOT NULL,
	text TEXT not null,
	PRIMARY KEY (id)
); 

CREATE TABLE contacts (
   id int(11) unsigned NOT NULL auto_increment,
   category int(11) unsigned DEFAULT '0' NOT NULL,
   owner int(11) unsigned DEFAULT '0' NOT NULL,
   firstname char(60) NOT NULL,
   lastname char(60) NOT NULL,
   hashname char(32) NOT NULL,
   email text NOT NULL,
   compagny char(255) NOT NULL,
   hometel char(255) NOT NULL,
   mobiletel char(255) NOT NULL,
   businesstel char(255) NOT NULL,
   businessfax char(255) NOT NULL,
   jobtitle char(255) NOT NULL,
   businessaddress text NOT NULL,
   homeaddress text NOT NULL,
   PRIMARY KEY (id),
   KEY hashname (hashname),
   KEY id (id)
);

#------- babinstall.sql
ALTER TABLE users CHANGE name nickname CHAR (30);
ALTER TABLE users ADD firstname CHAR (60) not null AFTER nickname , ADD lastname CHAR (60) not null AFTER firstname;
ALTER TABLE posts ADD id_parent INT (11) UNSIGNED not null AFTER id_thread;