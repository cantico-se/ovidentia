# phpMyAdmin MySQL-Dump
# http://phpwizard.net/phpMyAdmin/
#
# Serveur: localhost Base de données: bab

# --------------------------------------------------------
#
# Structure de la table 'articles'
#

CREATE TABLE articles (
   id int(11) unsigned NOT NULL auto_increment,
   id_topic int(11) unsigned DEFAULT '0' NOT NULL,
   id_author int(11) unsigned DEFAULT '0' NOT NULL,
   confirmed enum('Y','N') DEFAULT 'N' NOT NULL,
   date datetime,
   date_pub varchar(30),
   title tinytext NOT NULL,
   head text NOT NULL,
   body text NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'comments'
#

CREATE TABLE comments (
   id int(11) unsigned NOT NULL auto_increment,
   id_parent mediumint(11) unsigned DEFAULT '0' NOT NULL,
   id_article int(11) unsigned DEFAULT '0' NOT NULL,
   id_topic int(11) unsigned DEFAULT '0' NOT NULL,
   date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   subject tinytext NOT NULL,
   message text NOT NULL,
   confirmed enum('Y','N') DEFAULT 'N' NOT NULL,
   name varchar(60) NOT NULL,
   email tinytext NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'faqcat'
#

CREATE TABLE faqcat (
   id int(11) unsigned NOT NULL auto_increment,
   id_manager int(11) unsigned DEFAULT '0' NOT NULL,
   category varchar(60) NOT NULL,
   description text NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'faqcat_groups'
#

CREATE TABLE faqcat_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'faqqr'
#

CREATE TABLE faqqr (
   id int(11) unsigned NOT NULL auto_increment,
   idcat int(11) unsigned DEFAULT '0' NOT NULL,
   question text NOT NULL,
   response text NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'forums'
#

CREATE TABLE forums (
   id smallint(6) unsigned NOT NULL auto_increment,
   name varchar(30) NOT NULL,
   description varchar(100) NOT NULL,
   moderator int(11) unsigned DEFAULT '0' NOT NULL,
   moderation enum('N','Y') DEFAULT 'N' NOT NULL,
   display int(11) unsigned DEFAULT '0' NOT NULL,
   active enum('Y','N') DEFAULT 'Y' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'forumspost_groups'
#

CREATE TABLE forumspost_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'forumsreply_groups'
#

CREATE TABLE forumsreply_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'forumsview_groups'
#

CREATE TABLE forumsview_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'groups'
#

CREATE TABLE groups (
   id int(11) unsigned NOT NULL auto_increment,
   name varchar(20) NOT NULL,
   description varchar(200) NOT NULL,
   vacation enum('N','Y') DEFAULT 'N' NOT NULL,
   manager int(11) unsigned  DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO groups VALUES ( '1', 'Users', 'All registered users', 'N', '0');
INSERT INTO groups VALUES ( '2', 'Guests', 'all not registered users', 'N', '0');
INSERT INTO groups VALUES ( '3', 'Administrators', 'Manage the site', 'N', '0');

# --------------------------------------------------------
#
# Structure de la table 'notes'
#

CREATE TABLE notes (
   id int(11) unsigned NOT NULL auto_increment,
   id_user int(11) unsigned DEFAULT '0' NOT NULL,
   date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   content text NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'posts'
#

CREATE TABLE posts (
   id int(11) unsigned NOT NULL auto_increment,
   id_thread int(11) unsigned DEFAULT '0' NOT NULL,
   date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   dateupdate datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   author text NOT NULL,
   subject varchar(100) NOT NULL,
   message text NOT NULL,
   confirmed enum('N','Y') DEFAULT 'N' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'sections'
#

CREATE TABLE sections (
   id smallint(6) unsigned NOT NULL auto_increment,
   position enum('0','1') DEFAULT '0' NOT NULL,
   title varchar(60),
   description varchar(200),
   content text,
   script enum('N','Y') DEFAULT 'N' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'sections_groups'
#

CREATE TABLE sections_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'threads'
#

CREATE TABLE threads (
   id int(11) unsigned NOT NULL auto_increment,
   forum smallint(6) unsigned DEFAULT '0' NOT NULL,
   post int(11) unsigned DEFAULT '0' NOT NULL,
   lastpost int(11) unsigned DEFAULT '0' NOT NULL,
   views int(11) unsigned DEFAULT '0' NOT NULL,
   date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   active enum('N','Y') DEFAULT 'Y' NOT NULL,
   notify enum('N','Y') DEFAULT 'N' NOT NULL,
   starter int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'topics'
#

CREATE TABLE topics (
   id int(11) unsigned NOT NULL auto_increment,
   id_approver int(11) unsigned DEFAULT '0' NOT NULL,
   category varchar(60) NOT NULL,
   description text NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'topicscom_groups'
#

CREATE TABLE topicscom_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'topicssub_groups'
#

CREATE TABLE topicssub_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'topicsview_groups'
#

CREATE TABLE topicsview_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'users'
#

CREATE TABLE users (
   id int(11) unsigned NOT NULL auto_increment,
   name text,
   fullname text,
   email text,
   date datetime DEFAULT '0000-00-00 00:00:00',
   password text,
   changepwd tinyint(1) DEFAULT '0' NOT NULL,
   remote_addr text,
   confirm_hash text,
   is_confirmed tinyint(1) unsigned DEFAULT '0' NOT NULL,
   disabled tinyint(1) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO users VALUES ( '1', '', 'Administrator', 'admin@admin.bab', '2001-04-03 00:00:00', '22975d8a5ed1b91445f6c55ac121505b', '0', '', '0da8f2a37b9e7926e08196a6bd1baa29', '1', '0');

# --------------------------------------------------------
#
# Structure de la table 'users_groups'
#

CREATE TABLE users_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   isprimary enum('N','Y') DEFAULT 'N' NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO users_groups VALUES ( '1', '1', '3', 'N');

# --------------------------------------------------------
#
# Structure de la table 'users_log'
#

CREATE TABLE users_log (
   id int(11) unsigned NOT NULL auto_increment,
   id_user int(11) unsigned DEFAULT '0' NOT NULL,
   islogged enum('N','Y') DEFAULT 'N' NOT NULL,
   datelog datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   lastlog datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   dateact datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'vacations_types'
#

CREATE TABLE vacations_types (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (30) not null,
	description VARCHAR (224) not null,
	defaultdays TINYINT (3) not null,
	maxdays TINYINT (3) not null,
	days TINYINT (3) not null,
	PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'vacationsview_groups'
#

CREATE TABLE vacationsview_groups (
	id int(11) unsigned NOT NULL auto_increment,
	id_object int(11) unsigned DEFAULT '0' NOT NULL,
	id_group int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (id)
); 

# --------------------------------------------------------
#
# Structure de la table 'vacations_states'
#

CREATE TABLE vacations_states (
	id TINYINT (2) not null AUTO_INCREMENT,
	status VARCHAR (255) not null,
	description text NOT NULL,
	PRIMARY KEY (id)
);

INSERT INTO vacations_states VALUES ( '1', 'Refused', 'Vacation refused');
INSERT INTO vacations_states VALUES ( '2', 'Accepted', 'Vacation accepted');

# --------------------------------------------------------
#
# Structure de la table 'vacations'
#

CREATE TABLE vacations (
	id INT UNSIGNED not null AUTO_INCREMENT,
	userid INT UNSIGNED not null,
	datebegin DATETIME not null,
	dateend DATETIME not null,
	daybegin TINYINT (2) not null,
	dayend TINYINT (2) not null,
	type INT UNSIGNED not null,
	status TINYINT (2) not null,
	comment TINYTEXT not null,
	comref TEXT not null,
	date datetime DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (id)
); 

# --------------------------------------------------------
#
# Structure de la table 'vacationsman_groups'
#

CREATE TABLE vacationsman_groups (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_object INT (11) not null,
	id_group INT (11) not null,
	ordering SMALLINT (4) UNSIGNED not null,
	status TINYINT (2) not null,
	supplier INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
); 



# --------------------------------------------------------
#
# Structure de la table 'categoriescal'
#

CREATE TABLE categoriescal (
	id TINYINT (2) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (60) not null,
	description VARCHAR (255) not null,
	bgcolor VARCHAR (6) not null,
	id_group INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'resourcescal'
#

CREATE TABLE resourcescal (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (60) not null,
	description VARCHAR (255) not null,
	id_group INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'cal_events'
#

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



# --------------------------------------------------------
#
# Structure de la table 'calendar'
#

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
