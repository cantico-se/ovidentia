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
   mail enum('N','Y') DEFAULT 'N' NOT NULL,
   manager int(11) unsigned  DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO groups VALUES ( '1', 'Registered', 'All registered users', 'N', 'N', '0');
INSERT INTO groups VALUES ( '2', 'Guests', 'all not registered users', 'N', 'N', '0');
INSERT INTO groups VALUES ( '3', 'Administrators', 'Manage the site', 'N', 'N', '0');

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
   id_parent int(11) unsigned DEFAULT '0' NOT NULL,
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
   nickname varchar(255),
   firstname varchar(60),
   lastname varchar(30),
   hashname varchar(32),
   email text,
   date datetime DEFAULT '0000-00-00 00:00:00',
   password text,
   changepwd tinyint(1) DEFAULT '0' NOT NULL,
   remote_addr text,
   confirm_hash text,
   is_confirmed tinyint(1) unsigned DEFAULT '0' NOT NULL,
   disabled tinyint(1) unsigned DEFAULT '0' NOT NULL,
   lang varchar(10) NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO users VALUES ( '1', 'admin@admin.bab', 'Administrator', '', '200ceb26807d6bf99fd6f4f0d1ca54d4', 'admin@admin.bab', '2001-04-03 00:00:00', '22975d8a5ed1b91445f6c55ac121505b', '0', '', '0da8f2a37b9e7926e08196a6bd1baa29', '1', '0', '');

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


# --------------------------------------------------------
#
# Structure de la table 'calaccess_users'
#

CREATE TABLE calaccess_users (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_cal INT (11) UNSIGNED not null,
	id_user INT (11) UNSIGNED not null,
	bwrite enum('N','Y') DEFAULT 'N' NOT NULL,
	PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'caloptions'
#

CREATE TABLE caloptions (
	id INT (11) UNSIGNED not null AUTO_INCREMENT, 
	id_user INT (11) UNSIGNED not null, 
	startday TINYINT DEFAULT '0' not null, 
	allday ENUM ('Y','N') not null, 
	viewcat ENUM ('Y','N') not null, 
	usebgcolor ENUM ('Y','N') not null, 
	PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'mail_domains'
#

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


# --------------------------------------------------------
#
# Structure de la table 'mail_accounts'
#

CREATE TABLE mail_accounts (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (255) not null,
	email VARCHAR (255) not null,
	account VARCHAR (255) not null,
	password blob not null,
	domain INT (11) UNSIGNED not null,
	owner INT (11) UNSIGNED not null,
	maxrows TINYINT (2) not null,
	prefered enum('N','Y') DEFAULT 'N' NOT NULL,
	format VARCHAR (5) DEFAULT 'plain' NOT NULL,
	PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'mail_signatures'
#

CREATE TABLE mail_signatures (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	owner INT (11) UNSIGNED not null,
	html enum('Y','N') DEFAULT 'N' NOT NULL,
	text TEXT not null,
	PRIMARY KEY (id)
); 

# --------------------------------------------------------
#
# Structure de la table 'contacts'
#

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

CREATE TABLE sites (
   id int(11) unsigned NOT NULL auto_increment,
   name char(30) NOT NULL,
   description char(100) NOT NULL,
   lang char(10) NOT NULL,
   adminemail char(255) NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE homepages (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_article INT (11) UNSIGNED not null,
	id_site INT (11) UNSIGNED not null,
	id_group INT (11) UNSIGNED not null,
	status ENUM ('N', 'Y') not null,
	ordering INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);