# phpMyAdmin MySQL-Dump
# http://phpwizard.net/phpMyAdmin/
#
# Serveur: localhost Base de données: ovidentia

# --------------------------------------------------------
#
# Structure de la table 'bab_articles'
#

CREATE TABLE bab_articles (
   id int(11) unsigned NOT NULL auto_increment,
   id_topic int(11) unsigned DEFAULT '0' NOT NULL,
   id_author int(11) unsigned DEFAULT '0' NOT NULL,
   confirmed enum('Y','N') DEFAULT 'N' NOT NULL,
   date datetime,
   date_pub varchar(30),
   title tinytext NOT NULL,
   head text NOT NULL,
   body longtext NOT NULL,
   archive enum('N','Y') NOT NULL default 'N',
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_comments'
#

CREATE TABLE bab_comments (
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
# Structure de la table 'bab_faqcat'
#

CREATE TABLE bab_faqcat (
   id int(11) unsigned NOT NULL auto_increment,
   id_manager int(11) unsigned DEFAULT '0' NOT NULL,
   category varchar(60) NOT NULL,
   description text NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_faqcat_groups'
#

CREATE TABLE bab_faqcat_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_faqqr'
#

CREATE TABLE bab_faqqr (
   id int(11) unsigned NOT NULL auto_increment,
   idcat int(11) unsigned DEFAULT '0' NOT NULL,
   question text NOT NULL,
   response text NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_forums'
#

CREATE TABLE bab_forums (
   id smallint(6) unsigned NOT NULL auto_increment,
   name varchar(30) NOT NULL,
   description varchar(100) NOT NULL,
   moderator int(11) unsigned DEFAULT '0' NOT NULL,
   moderation enum('N','Y') DEFAULT 'N' NOT NULL,
   notification enum('N','Y') DEFAULT 'N' NOT NULL,
   display int(11) unsigned DEFAULT '0' NOT NULL,
   active enum('Y','N') DEFAULT 'Y' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_forumspost_groups'
#

CREATE TABLE bab_forumspost_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'forumsreply_groups'
#

CREATE TABLE bab_forumsreply_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_forumsview_groups'
#

CREATE TABLE bab_forumsview_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_groups'
#

CREATE TABLE bab_groups (
   id int(11) unsigned NOT NULL auto_increment,
   name varchar(20) NOT NULL,
   description varchar(200) NOT NULL,
   vacation enum('N','Y') DEFAULT 'N' NOT NULL,
   mail enum('N','Y') DEFAULT 'N' NOT NULL,
   manager int(11) unsigned  DEFAULT '0' NOT NULL,
   gstorage enum('N','Y') DEFAULT 'N' NOT NULL,
   ustorage enum('N','Y') DEFAULT 'N' NOT NULL,
   moderate enum('Y','N') DEFAULT 'Y' NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO bab_groups VALUES ( '1', 'Registered', 'All registered users', 'N', 'N', '0', 'N', 'N', 'Y');
INSERT INTO bab_groups VALUES ( '2', 'Guests', 'all not registered users', 'N', 'N', '0', 'N', 'N', 'Y');
INSERT INTO bab_groups VALUES ( '3', 'Administrators', 'Manage the site', 'N', 'N', '0', 'N', 'N', 'Y');

# --------------------------------------------------------
#
# Structure de la table 'bab_notes'
#

CREATE TABLE bab_notes (
   id int(11) unsigned NOT NULL auto_increment,
   id_user int(11) unsigned DEFAULT '0' NOT NULL,
   date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   content text NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_posts'
#

CREATE TABLE bab_posts (
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
# Structure de la table 'bab_sections'
#

CREATE TABLE bab_sections (
   id smallint(6) unsigned NOT NULL auto_increment,
   position enum('0','1') DEFAULT '0' NOT NULL,
   title varchar(60),
   description varchar(200),
   content text,
   script enum('N','Y') DEFAULT 'N' NOT NULL,
   jscript enum('N','Y') DEFAULT 'N' NOT NULL,
   enabled enum('Y','N') DEFAULT 'Y' NOT NULL,
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_private_sections'
#

CREATE TABLE bab_private_sections (
   id smallint(6) unsigned NOT NULL auto_increment,
   position enum('0','1') DEFAULT '0' NOT NULL,
   title varchar(60),
   description varchar(200),
   enabled enum('Y','N') DEFAULT 'Y' NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO bab_private_sections VALUES ('1', '0', 'Administration', 'This section is for Administration', 'Y');
INSERT INTO bab_private_sections VALUES ('2', '1', 'Month', 'This section shows calendar month', 'Y');
INSERT INTO bab_private_sections VALUES ('3', '0', 'Topics categories', 'This section lists topics', 'Y');
INSERT INTO bab_private_sections VALUES ('4', '0', 'Forums', 'This section lists forums', 'Y');
INSERT INTO bab_private_sections VALUES ('5', '1', 'User\'s section', 'This section is for User', 'Y');

# --------------------------------------------------------
#
# Structure de la table 'bab_sections_order'
#

CREATE TABLE bab_sections_order (
   id smallint(6) unsigned NOT NULL auto_increment,
   id_section smallint(6) unsigned NOT NULL,
   position enum('0','1') DEFAULT '0' NOT NULL,
   type smallint(2) unsigned NOT NULL,
   ordering smallint(6) unsigned NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO bab_sections_order VALUES ('1', '1', '0', '1', '1');
INSERT INTO bab_sections_order VALUES ('2', '2', '1', '1', '1');
INSERT INTO bab_sections_order VALUES ('3', '3', '0', '1', '2');
INSERT INTO bab_sections_order VALUES ('4', '4', '0', '1', '3');
INSERT INTO bab_sections_order VALUES ('5', '5', '1', '1', '2');
INSERT INTO bab_sections_order VALUES ('6', '1', '0', '3', '4');

# --------------------------------------------------------
#
# Structure de la table 'bab_sections_states'
#

CREATE TABLE bab_sections_states (
   id int(11) unsigned NOT NULL auto_increment,
   id_section smallint(6) unsigned NOT NULL,
   closed enum('N','Y') DEFAULT 'N' NOT NULL,
   type smallint(2) unsigned NOT NULL,
   id_user int(11) unsigned NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_sections_groups'
#

CREATE TABLE bab_sections_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_threads'
#

CREATE TABLE bab_threads (
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
# Structure de la table 'bab_topics'
#

CREATE TABLE bab_topics (
   id int(11) unsigned NOT NULL auto_increment,
   id_approver int(11) unsigned DEFAULT '0' NOT NULL,
   category varchar(60) NOT NULL,
   description text NOT NULL,
   id_cat int(11) unsigned DEFAULT '0' NOT NULL,
   mod_com enum('Y','N') NOT NULL default 'Y',
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_topics_categories'
#

CREATE TABLE bab_topics_categories (
   id int(11) unsigned NOT NULL auto_increment,
   title varchar(60),
   description varchar(200),
   enabled enum('Y','N') DEFAULT 'Y' NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO bab_topics_categories VALUES ('1', 'Default category', 'Default category', 'Y');

# --------------------------------------------------------
#
# Structure de la table 'bab_topicscom_groups'
#

CREATE TABLE bab_topicscom_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_topicssub_groups'
#

CREATE TABLE bab_topicssub_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_topicsview_groups'
#

CREATE TABLE bab_topicsview_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_users'
#

CREATE TABLE bab_users (
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
   skin text,
   style text,
   lastlog datetime DEFAULT '0000-00-00 00:00:00',
   datelog datetime DEFAULT '0000-00-00 00:00:00',
   PRIMARY KEY (id)
);

INSERT INTO bab_users VALUES ( '1', 'admin@admin.bab', 'Administrator', '', '200ceb26807d6bf99fd6f4f0d1ca54d4', 'admin@admin.bab', '2001-04-03 00:00:00', '22975d8a5ed1b91445f6c55ac121505b', '1', '', '0da8f2a37b9e7926e08196a6bd1baa29', '1', '0', '', '', '', '', '');

# --------------------------------------------------------
#
# Structure de la table 'bab_users_groups'
#

CREATE TABLE bab_users_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   isprimary enum('N','Y') DEFAULT 'N' NOT NULL,
   PRIMARY KEY (id)
);

INSERT INTO bab_users_groups VALUES ( '1', '1', '3', 'N');

# --------------------------------------------------------
#
# Structure de la table 'bab_users_log'
#

CREATE TABLE bab_users_log (
   id int(11) unsigned NOT NULL auto_increment,
   id_user int(11) unsigned DEFAULT '0' NOT NULL,
   dateact timestamp(14) NOT NULL,
   sessid tinytext NOT NULL,
   remote_addr varchar(255) NOT NULL default '',
   forwarded_for varchar(255) NOT NULL default '',
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_vacations_types'
#

CREATE TABLE bab_vacations_types (
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
# Structure de la table 'bab_vacationsview_groups'
#

CREATE TABLE bab_vacationsview_groups (
	id int(11) unsigned NOT NULL auto_increment,
	id_object int(11) unsigned DEFAULT '0' NOT NULL,
	id_group int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (id)
); 

# --------------------------------------------------------
#
# Structure de la table 'bab_vacations_states'
#

CREATE TABLE bab_vacations_states (
	id TINYINT (2) not null AUTO_INCREMENT,
	status VARCHAR (255) not null,
	description text NOT NULL,
	PRIMARY KEY (id)
);

INSERT INTO bab_vacations_states VALUES ( '1', 'Refused', 'Vacation refused');
INSERT INTO bab_vacations_states VALUES ( '2', 'Accepted', 'Vacation accepted');

# --------------------------------------------------------
#
# Structure de la table 'bab_vacations'
#

CREATE TABLE bab_vacations (
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
# Structure de la table 'bab_vacationsman_groups'
#

CREATE TABLE bab_vacationsman_groups (
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
# Structure de la table 'bab_categoriescal'
#

CREATE TABLE bab_categoriescal (
	id TINYINT (2) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (60) not null,
	description VARCHAR (255) not null,
	bgcolor VARCHAR (6) not null,
	id_group INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_resourcescal'
#

CREATE TABLE bab_resourcescal (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (60) not null,
	description VARCHAR (255) not null,
	id_group INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_events'
#

CREATE TABLE bab_cal_events (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_cal INT (11) UNSIGNED not null,
	title VARCHAR (255) not null,
	description TEXT not null,
	start_date DATE not null,
	start_time TIME not null,
	end_date DATE not null,
	end_time TIME not null,
	id_cat INT (11) UNSIGNED not null,
	id_creator INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);



# --------------------------------------------------------
#
# Structure de la table 'bab_calendar'
#

CREATE TABLE bab_calendar (
	id int(11) unsigned NOT NULL auto_increment,
	owner int(11) unsigned DEFAULT '0' NOT NULL,
	actif enum('Y','N') DEFAULT 'Y' NOT NULL,
	type TINYINT (2) not null,
	PRIMARY KEY (id)
);

INSERT INTO bab_calendar VALUES ( '1', '1', 'Y', '1');
INSERT INTO bab_calendar VALUES ( '2', '1', 'Y', '2');
INSERT INTO bab_calendar VALUES ( '3', '2', 'N', '2');
INSERT INTO bab_calendar VALUES ( '4', '3', 'Y', '2');


# --------------------------------------------------------
#
# Structure de la table 'bab_calaccess_users'
#

CREATE TABLE bab_calaccess_users (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_cal INT (11) UNSIGNED not null,
	id_user INT (11) UNSIGNED not null,
    bwrite smallint(2) unsigned NOT NULL,
	PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_caloptions'
#

CREATE TABLE bab_caloptions (
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
# Structure de la table 'bab_mail_domains'
#

CREATE TABLE bab_mail_domains (
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
# Structure de la table 'bab_mail_accounts'
#

CREATE TABLE bab_mail_accounts (
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
# Structure de la table 'bab_mail_signatures'
#

CREATE TABLE bab_mail_signatures (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	owner INT (11) UNSIGNED not null,
	html enum('Y','N') DEFAULT 'N' NOT NULL,
	text TEXT not null,
	PRIMARY KEY (id)
); 

# --------------------------------------------------------
#
# Structure de la table 'bab_contacts'
#

CREATE TABLE bab_contacts (
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

# --------------------------------------------------------
#
# Structure de la table 'bab_sites'
#

CREATE TABLE bab_sites (
   id int(11) unsigned NOT NULL auto_increment,
   name char(30) NOT NULL,
   description char(100) NOT NULL,
   lang char(10) NOT NULL,
   adminemail char(255) NOT NULL,
   skin char(255) NOT NULL,
   style char(255) NOT NULL,
   registration enum('Y','N') DEFAULT 'Y' NOT NULL,
   email_confirm enum('Y','N') DEFAULT 'Y' NOT NULL,
   mailfunc char(20) NOT NULL DEFAUL 'mail',
   smtpserver char(255) NOT NULL DEFAUL '',
   smtpport char(20) NOT NULL DEFAUL '25',
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_homepages'
#

CREATE TABLE bab_homepages (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_article INT (11) UNSIGNED not null,
	id_site INT (11) UNSIGNED not null,
	id_group INT (11) UNSIGNED not null,
	status ENUM ('N', 'Y') not null,
	ordering INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_files'
#

CREATE TABLE bab_files (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description tinytext NOT NULL,
  keywords tinytext NOT NULL,
  path tinytext NOT NULL,
  id_owner int(11) unsigned NOT NULL default '0',
  bgroup enum('N','Y') NOT NULL default 'N',
  link int(11) unsigned NOT NULL default '0',
  readonly enum('N','Y') NOT NULL default 'N',
  state char(1) NOT NULL default '',
  created datetime default NULL,
  author int(11) unsigned NOT NULL default '0',
  modified datetime default NULL,
  modifiedby int(11) unsigned NOT NULL default '0',
  confirmed enum('N','Y') NOT NULL default 'N',
  hits int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_mime_types'
#

CREATE TABLE bab_mime_types (
	ext VARCHAR(10) NOT NULL, 
	mimetype TINYTEXT NOT NULL,
	PRIMARY KEY (ext)
); 

INSERT INTO bab_mime_types VALUES ('ai', 'application/postscript');
INSERT INTO bab_mime_types VALUES ('asc', 'text/plain');
INSERT INTO bab_mime_types VALUES ('au', 'audio/basic');
INSERT INTO bab_mime_types VALUES ('avi', 'video/x-msvideo');
INSERT INTO bab_mime_types VALUES ('bin', 'application/octet-stream');
INSERT INTO bab_mime_types VALUES ('bmp', 'image/bmp');
INSERT INTO bab_mime_types VALUES ('class', 'application/octet-stream');
INSERT INTO bab_mime_types VALUES ('css', 'text/css');
INSERT INTO bab_mime_types VALUES ('doc', 'application/msword');
INSERT INTO bab_mime_types VALUES ('dvi', 'application/x-dvi');
INSERT INTO bab_mime_types VALUES ('exe', 'application/octet-stream');
INSERT INTO bab_mime_types VALUES ('gif', 'image/gif');
INSERT INTO bab_mime_types VALUES ('htm', 'text/html');
INSERT INTO bab_mime_types VALUES ('html', 'text/html');
INSERT INTO bab_mime_types VALUES ('jpe', 'image/jpeg');
INSERT INTO bab_mime_types VALUES ('jpeg', 'image/jpeg');
INSERT INTO bab_mime_types VALUES ('jpg', 'image/jpeg');
INSERT INTO bab_mime_types VALUES ('js', 'application/x-javascript');
INSERT INTO bab_mime_types VALUES ('mid', 'audio/midi');
INSERT INTO bab_mime_types VALUES ('midi', 'audio/midi');
INSERT INTO bab_mime_types VALUES ('mp3', 'audio/mpeg');
INSERT INTO bab_mime_types VALUES ('mpeg', 'video/mpeg');
INSERT INTO bab_mime_types VALUES ('png', 'image/png');
INSERT INTO bab_mime_types VALUES ('ppt', 'application/vnd.ms-powerpoint');
INSERT INTO bab_mime_types VALUES ('ps', 'application/postscript');
INSERT INTO bab_mime_types VALUES ('rtf', 'text/rtf');
INSERT INTO bab_mime_types VALUES ('tar', 'application/x-tar');
INSERT INTO bab_mime_types VALUES ('txt', 'text/plain');
INSERT INTO bab_mime_types VALUES ('wav', 'audio/x-wav');
INSERT INTO bab_mime_types VALUES ('xls', 'application/vnd.ms-excel');
INSERT INTO bab_mime_types VALUES ('xml', 'text/xml');
INSERT INTO bab_mime_types VALUES ('zip', 'application/zip');

# --------------------------------------------------------
#
# Structure de la table 'bab_addons'
#

CREATE TABLE bab_addons (
  id int(11) unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  enabled enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (id)
);

    
# --------------------------------------------------------
#
# Structure de la table 'bab_addons_groups'
#

CREATE TABLE bab_addons_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE bab_ini (
	foption char(255) NOT NULL default '',
	fvalue char(255) NOT NULL default '',
	UNIQUE KEY foption (foption)
);

INSERT INTO bab_ini VALUES ('ver_major', '3');
INSERT INTO bab_ini VALUES ('ver_minor', '3');
INSERT INTO bab_ini VALUES ('ver_build', '1');