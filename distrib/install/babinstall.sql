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
  id_topic int(11) unsigned NOT NULL default '0',
  id_author int(11) unsigned NOT NULL default '0',
  date datetime default NULL,
  date_publication datetime NOT NULL default '0000-00-00 00:00:00',
  date_archiving datetime NOT NULL default '0000-00-00 00:00:00',
  date_modification datetime NOT NULL default '0000-00-00 00:00:00',
  title tinytext NOT NULL,
  head mediumtext NOT NULL,
  body longtext NOT NULL,
  archive enum('N','Y') NOT NULL default 'N',
  lang varchar(10) NOT NULL default '',
  restriction varchar(255) NOT NULL default '',
  ordering int(11) unsigned NOT NULL default '0',
  id_modifiedby int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_topic (id_topic),
  KEY id_author (id_author),
  KEY date_publication (date_publication),
  KEY date_archiving (date_archiving),
  KEY date (date)
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
   idfai int(11) unsigned NOT NULL default '0',
   lang varchar(10) NOT NULL default '',
   PRIMARY KEY (id),
   KEY id_article (id_article),
   KEY id_topic (id_topic),
   KEY idfai (idfai),
   KEY date (date)
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
   lang varchar(10) NOT NULL default '',
   id_dgowner int(11) unsigned NOT NULL default '0',
   id_root int(11) unsigned NOT NULL default '0',
   PRIMARY KEY  (id),
   KEY id_dgowner (id_dgowner)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_faqcat_groups'
#

CREATE TABLE bab_faqcat_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_faqqr'
#

CREATE TABLE bab_faqqr (
   id int(11) unsigned NOT NULL auto_increment,
   idcat int(11) unsigned DEFAULT '0' NOT NULL,
   id_subcat int(11) unsigned DEFAULT '0' NOT NULL,
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
   moderation enum('N','Y') DEFAULT 'N' NOT NULL,
   notification enum('N','Y') DEFAULT 'N' NOT NULL,
   display int(11) unsigned DEFAULT '0' NOT NULL,
   active enum('Y','N') DEFAULT 'Y' NOT NULL,
   ordering smallint(6) unsigned NOT NULL default '0',
   id_dgowner int(11) unsigned NOT NULL default '0',
   PRIMARY KEY  (id),
   KEY id_dgowner (id_dgowner)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_forumspost_groups'
#

CREATE TABLE bab_forumspost_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);


# --------------------------------------------------------
#
# Structure de la table 'forumsreply_groups'
#

CREATE TABLE bab_forumsreply_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_forumsview_groups'
#

CREATE TABLE bab_forumsview_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_groups'
#

CREATE TABLE bab_groups (
   id int(11) unsigned NOT NULL auto_increment,
   name varchar(255) NOT NULL,
   description varchar(255) NOT NULL,
   mail enum('N','Y') DEFAULT 'N' NOT NULL,
   manager int(11) unsigned  DEFAULT '0' NOT NULL,
   ustorage enum('N','Y') DEFAULT 'N' NOT NULL,
   notes enum('Y','N') NOT NULL default 'Y',
   contacts enum('Y','N') NOT NULL default 'Y',
   directory enum('N','Y') NOT NULL default 'N',
   pcalendar enum('Y','N') NOT NULL default 'Y',
   id_dggroup int(11) unsigned NOT NULL default '0',
   id_dgowner int(11) unsigned NOT NULL default '0',
   id_ocentity int(11) unsigned NOT NULL default '0',
   PRIMARY KEY (id),
   KEY manager (manager),
   KEY id_dggroup (id_dggroup),
   KEY id_dgowner (id_dgowner)
);

INSERT INTO bab_groups VALUES ( '1', 'Registered', 'All registered users', 'N', '0', 'N', 'Y', 'Y', 'Y', 'Y', '0', '0', '0');
INSERT INTO bab_groups VALUES ( '2', 'Guests', 'all not registered users', 'N', '0', 'N', 'N', 'N', 'N', 'N', '0', '0', '0');
INSERT INTO bab_groups VALUES ( '3', 'Administrators', 'Manage the site', 'N', '0', 'N', 'Y', 'Y', 'N', 'Y', '0', '0', '0');


# --------------------------------------------------------
#
# Structure de la table 'bab_notes'
#

CREATE TABLE bab_notes (
   id int(11) unsigned NOT NULL auto_increment,
   id_user int(11) unsigned DEFAULT '0' NOT NULL,
   date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   content text NOT NULL,
   PRIMARY KEY (id),
   KEY id_user (id_user)
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
   PRIMARY KEY (id),
   KEY id_thread (id_thread),
   KEY id_parent (id_parent),
   KEY date (date)
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
   template varchar(255),
   lang varchar(10) NOT NULL default '',
   id_dgowner int(11) unsigned NOT NULL default '0',
   optional enum('N','Y') NOT NULL default 'N',
   PRIMARY KEY (id),
   KEY id_dgowner (id_dgowner)
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
   optional enum('N','Y') NOT NULL default 'N',
   PRIMARY KEY (id)
);

INSERT INTO bab_private_sections VALUES ('1', '0', 'Administration', 'This section is for Administration', 'Y', 'N');
INSERT INTO bab_private_sections VALUES ('2', '1', 'Month', 'This section shows calendar month', 'Y', 'N');
INSERT INTO bab_private_sections VALUES ('3', '0', 'Topics categories', 'This section lists topics', 'Y', 'N');
INSERT INTO bab_private_sections VALUES ('4', '0', 'Forums', 'This section lists forums', 'Y', 'N');
INSERT INTO bab_private_sections VALUES ('5', '1', 'User\'s section', 'This section is for User', 'Y', 'N');

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
   PRIMARY KEY (id),
   KEY id_section (id_section),
   KEY type (type),
   KEY ordering (ordering)
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
   hidden enum('N','Y') DEFAULT 'N' NOT NULL,
   PRIMARY KEY (id),
   KEY id_section (id_section),
   KEY type (type),
   KEY id_user (id_user)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_sections_groups'
#

CREATE TABLE bab_sections_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
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
   PRIMARY KEY (id),
   KEY forum (forum),
   KEY date (date)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_topics'
#

CREATE TABLE bab_topics (
  id int(11) unsigned NOT NULL auto_increment,
  category varchar(60) NOT NULL default '',
  description text NOT NULL,
  id_cat int(11) unsigned NOT NULL default '0',
  idsaart int(11) unsigned NOT NULL default '0',
  idsacom int(11) unsigned NOT NULL default '0',
  idsa_update int(11) unsigned NOT NULL default '0',
  notify enum('N','Y') NOT NULL default 'N',
  lang varchar(10) NOT NULL default '',
  article_tmpl varchar(255) default NULL,
  display_tmpl varchar(255) default NULL,
  restrict_access enum('N','Y') NOT NULL default 'N',
  allow_hpages enum('N','Y') NOT NULL default 'N',
  allow_pubdates enum('N','Y') NOT NULL default 'N',
  allow_attachments enum('N','Y') NOT NULL default 'N',
  allow_update enum('0','1','2') NOT NULL default '0',
  max_articles tinyint(3) unsigned NOT NULL default '0',
  allow_manupdate enum('0','1','2') NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_cat (id_cat)
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
   template varchar(255),
   id_dgowner int(11) unsigned NOT NULL default '0',
   optional enum('N','Y') NOT NULL default 'N',
   id_parent int(11) unsigned NOT NULL default '0',
   display_tmpl varchar(255),
   PRIMARY KEY (id),
   KEY id_dgowner (id_dgowner)
);

INSERT INTO bab_topics_categories VALUES ('1', 'Default category', 'Default category', 'Y', '', '0', 'N', '0', '');

# --------------------------------------------------------
#
# Structure de la table `bab_topcat_order`
#

CREATE TABLE bab_topcat_order (
  id int(11) unsigned NOT NULL auto_increment,
  id_topcat int(11) unsigned NOT NULL default '0',
  type smallint(2) unsigned NOT NULL default '0',
  ordering smallint(2) unsigned NOT NULL default '0',
  id_parent int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_topcat (id_topcat),
  KEY id_parent (id_parent)
);

INSERT INTO bab_topcat_order (id_topcat, type, ordering) VALUES ('1', '1', '1');

# --------------------------------------------------------
#
# Structure de la table 'bab_topicscom_groups'
#

CREATE TABLE bab_topicscom_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_topicssub_groups'
#

CREATE TABLE bab_topicssub_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_topicsview_groups'
#

CREATE TABLE bab_topicsview_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
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
   langfilter INTEGER DEFAULT 0,
   PRIMARY KEY (id),
   KEY nickname (nickname),
   KEY firstname (firstname),
   KEY lastname (lastname),
   KEY hashname (hashname)
);

INSERT INTO bab_users VALUES ( '1', 'admin@admin.bab', 'Administrator', '', '200ceb26807d6bf99fd6f4f0d1ca54d4', 'admin@admin.bab', '2001-04-03 00:00:00', '22975d8a5ed1b91445f6c55ac121505b', '1', '', '0da8f2a37b9e7926e08196a6bd1baa29', '1', '0', '', '', '', '', '', '');

# --------------------------------------------------------
#
# Structure de la table 'bab_users_groups'
#

CREATE TABLE bab_users_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   isprimary enum('N','Y') DEFAULT 'N' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
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
   id_dggroup int(11) unsigned NOT NULL default '0',
   cnx_try int(2) unsigned NOT NULL default '0',
   PRIMARY KEY (id),
   KEY id_user (id_user)
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
	PRIMARY KEY (id),
    KEY id_group (id_group)
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
	PRIMARY KEY (id),
    KEY id_group (id_group)
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
    hash varchar(34) NOT NULL default '',
	PRIMARY KEY (id),
    KEY id_cal (id_cal),
    KEY start_date (start_date),
    KEY end_date (end_date)
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
	PRIMARY KEY (id),
    KEY owner (owner),
    KEY type (type)
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
	PRIMARY KEY (id),
    KEY id_cal (id_cal),
    KEY id_user (id_user)
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
	ampm ENUM ('Y','N') not null, 
	usebgcolor ENUM ('Y','N') not null, 
    elapstime tinyint(2) unsigned NOT NULL DEFAULT '30' ,
    defaultview tinyint(3) NOT NULL default '0',
    defaultviewweek tinyint(3) NOT NULL default '0',
	PRIMARY KEY (id),
    KEY id_user (id_user)
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
    id_dgowner int(11) unsigned NOT NULL default '0',
	PRIMARY KEY (id),
    KEY owner (owner)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_mail_accounts'
#

CREATE TABLE bab_mail_accounts (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	account_name VARCHAR (255) not null,
	name VARCHAR (255) not null,
	email VARCHAR (255) not null,
	login VARCHAR (255) not null,
	password blob not null,
	domain INT (11) UNSIGNED not null,
	owner INT (11) UNSIGNED not null,
	maxrows TINYINT (2) not null,
	prefered enum('N','Y') DEFAULT 'N' NOT NULL,
	format VARCHAR (5) DEFAULT 'plain' NOT NULL,
	PRIMARY KEY (id),
    KEY owner (owner)
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
	PRIMARY KEY (id),
    KEY owner (owner)
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
   KEY owner (owner),
   KEY firstname (firstname),
   KEY lastname (lastname)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_sites'
#

CREATE TABLE `bab_sites` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(30) NOT NULL default '',
  `description` varchar(100) NOT NULL default '',
  `lang` varchar(10) NOT NULL default '',
  `adminemail` varchar(255) NOT NULL default '',
  `adminname` varchar(255) NOT NULL default '',
  `skin` varchar(255) NOT NULL default '',
  `style` varchar(255) NOT NULL default '',
  `registration` enum('Y','N') NOT NULL default 'Y',
  `display_disclaimer` enum('N','Y') NOT NULL default 'N',
  `email_confirm` enum('Y','N') NOT NULL default 'Y',
  `mailfunc` varchar(20) NOT NULL default 'mail',
  `smtpserver` varchar(255) NOT NULL default '',
  `smtpport` varchar(20) NOT NULL default '25',
  `imgsize` int(11) unsigned NOT NULL default '0',
  `idgroup` int(11) unsigned NOT NULL default '0',
  `smtpuser` varchar(255) NOT NULL default '',
  `smtppassword` tinyblob NOT NULL,
  `langfilter` int(11) default '0',
  `total_diskspace` int(11) unsigned NOT NULL default '0',
  `user_diskspace` int(11) unsigned NOT NULL default '0',
  `folder_diskspace` int(11) unsigned NOT NULL default '0',
  `maxfilesize` int(11) unsigned NOT NULL default '0',
  `uploadpath` varchar(255) NOT NULL default '',
  `babslogan` varchar(255) NOT NULL default '',
  `remember_login` enum('Y','N','L') NOT NULL default 'N',
  `change_password` enum('Y','N') NOT NULL default 'Y',
  `change_nickname` enum('Y','N') NOT NULL default 'Y',
  `name_order` enum('F L','L F') NOT NULL default 'F L',
  `email_password` enum('Y','N') NOT NULL default 'Y',
  `authentification` smallint(5) unsigned NOT NULL default '0',
  `ldap_host` tinytext NOT NULL,
  `ldap_basedn` text NOT NULL,
  `ldap_userdn` text NOT NULL,
  `ldap_password` tinyblob NOT NULL,
  `ldap_searchdn` text NOT NULL,
  `ldap_attribute` text NOT NULL,
  `ldap_passwordtype` enum('text','md5','unix','sha') NOT NULL default 'text',
  `ldap_allowadmincnx` enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
);


INSERT INTO bab_sites (id, name, description, lang, adminemail,  adminname, skin, style) values ('1', 'Ovidentia', 'Ovidentia site', 'en', 'admin@your-domain.com', 'Ovidentia Administrator', 'ovidentia_mp', 'ovidentia.css');


# --------------------------------------------------------
#
# Structure de la table 'bab_topicscom_groups'
#

CREATE TABLE bab_sites_hpman_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

INSERT INTO bab_sites_hpman_groups (id_object, id_group) values ('1', '3');

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
	PRIMARY KEY (id),
    KEY id_site (id_site),
    KEY id_group (id_group),
    KEY ordering (ordering)
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
  idfai int(11) unsigned NOT NULL default '0',
  edit int(11) unsigned NOT NULL default '0',
  ver_major smallint(5) unsigned NOT NULL default '1',
  ver_minor smallint(5) unsigned NOT NULL default '0',
  ver_comment tinytext NOT NULL,
  PRIMARY KEY  (id),
  KEY id_owner (id_owner),
  KEY name (name)
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

CREATE TABLE `bab_addons` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `enabled` enum('Y','N') NOT NULL default 'Y',
  `version` varchar(127) NOT NULL default '',
  PRIMARY KEY  (`id`)
);

    
# --------------------------------------------------------
#
# Structure de la table 'bab_addons_groups'
#

CREATE TABLE bab_addons_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

CREATE TABLE bab_ini (
	foption char(255) NOT NULL default '',
	fvalue char(255) NOT NULL default '',
	UNIQUE KEY foption (foption)
);

INSERT INTO bab_ini VALUES ('ver_major', '5');
INSERT INTO bab_ini VALUES ('ver_minor', '0');
INSERT INTO bab_ini VALUES ('ver_build', '0');
INSERT INTO bab_ini VALUES ('ver_prod', 'E');

#
# Structure de la table `bab_images_temp`
#

CREATE TABLE bab_images_temp (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  id_owner int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
);


#
# Structure de la table `bab_flow_approvers`
#

CREATE TABLE bab_flow_approvers (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description tinytext NOT NULL,
  formula tinytext NOT NULL,
  forder enum('N','Y') NOT NULL default 'N',
  refcount int(11) unsigned NOT NULL default '0',
  id_dgowner int(11) unsigned NOT NULL default '0',
  satype tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_dgowner (id_dgowner),
  KEY satype (satype)
);

#
# Structure de la table `bab_fa_instances`
#

CREATE TABLE bab_fa_instances (
  id int(11) unsigned NOT NULL auto_increment,
  idsch int(11) unsigned NOT NULL default '0',
  extra tinytext NOT NULL,
  iduser int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idsch (idsch)
);

#
# Structure de la table `bab_far_instances`
#

CREATE TABLE bab_far_instances (
  id int(11) unsigned NOT NULL auto_increment,
  idschi int(11) unsigned NOT NULL default '0',
  iduser int(11) NOT NULL default '0',
  result char(1) NOT NULL default '',
  notified enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (id)
);


#
# Structure de la table `bab_fm_folders`
#

CREATE TABLE bab_fm_folders (
  id int(11) unsigned NOT NULL auto_increment,
  folder char(255) NOT NULL default '',
  manager int(11) unsigned NOT NULL default '0',
  idsa int(11) unsigned NOT NULL default '0',
  filenotify enum('N','Y') NOT NULL default 'N',
  active enum('Y','N') NOT NULL default 'Y',
  version enum('N','Y') NOT NULL default 'N',
  id_dgowner int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY folder (folder),
  KEY id_dgowner (id_dgowner)
);

#
# Structure de la table `bab_fmdownload_groups`
#

CREATE TABLE bab_fmdownload_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_fmupdate_groups`
#

CREATE TABLE bab_fmupdate_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_fmupload_groups`
#

CREATE TABLE bab_fmupload_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table `bab_db_directories`
#

CREATE TABLE bab_db_directories (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  id_group int(11) unsigned NOT NULL default '0',
  id_dgowner int(11) unsigned NOT NULL default '0',
  user_update enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (id),
  KEY id_dgowner (id_dgowner)
);

INSERT INTO bab_db_directories (id, name, description, id_group, id_dgowner) values (1, 'Ovidentia', 'Ovidentia directory', '1', '0');

#
# Structure de la table `bab_dbdir_entries`
#

CREATE TABLE bab_dbdir_entries (
  id int(11) unsigned NOT NULL auto_increment,
  cn varchar(255) NOT NULL default '',
  sn varchar(255) NOT NULL default '',
  mn varchar(255) NOT NULL default '',
  givenname varchar(255) NOT NULL default '',
  jpegphoto varchar(255) NOT NULL default '',
  email text NOT NULL,
  btel varchar(255) NOT NULL default '',
  mobile varchar(255) NOT NULL default '',
  htel varchar(255) NOT NULL default '',
  bfax varchar(255) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  departmentnumber varchar(255) NOT NULL default '',
  organisationname varchar(255) NOT NULL default '',
  bstreetaddress text NOT NULL,
  bcity varchar(255) NOT NULL default '',
  bpostalcode varchar(10) NOT NULL default '',
  bstate varchar(255) NOT NULL default '',
  bcountry varchar(255) NOT NULL default '',
  hstreetaddress text NOT NULL,
  hcity varchar(255) NOT NULL default '',
  hpostalcode varchar(10) NOT NULL default '',
  hstate varchar(255) NOT NULL default '',
  hcountry varchar(255) NOT NULL default '',
  user1 text NOT NULL,
  user2 text NOT NULL,
  user3 text NOT NULL,
  photo_data longblob NOT NULL,
  photo_type varchar(20) NOT NULL default '',
  id_directory int(11) unsigned NOT NULL default '0',
  id_user int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY sn (sn),
  KEY mn (mn),
  KEY givenname (givenname),
  KEY id_directory (id_directory)
);

INSERT INTO bab_dbdir_entries (sn, email, id_directory, id_user) VALUES ('Administrator', 'admin@admin.bab', '0', '1');


#
# Structure de la table `bab_dbdir_fields`
#

CREATE TABLE bab_dbdir_fields (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  x_name varchar(255) NOT NULL default '',
  description tinytext NOT NULL,
  PRIMARY KEY  (id),
  KEY name (name)
);

INSERT INTO bab_dbdir_fields VALUES (1, 'cn', 'cn', 'Common Name');
INSERT INTO bab_dbdir_fields VALUES (2, 'sn', 'sn', 'Last Name');
INSERT INTO bab_dbdir_fields VALUES (3, 'mn', '', 'Middle Name');
INSERT INTO bab_dbdir_fields VALUES (4, 'givenname', 'givenname', 'First Name');
INSERT INTO bab_dbdir_fields VALUES (5, 'jpegphoto', 'jpegphoto', 'Photo');
INSERT INTO bab_dbdir_fields VALUES (6, 'email', 'mail', 'E-mail Address');
INSERT INTO bab_dbdir_fields VALUES (7, 'btel', 'telephonenumber', 'Business Phone');
INSERT INTO bab_dbdir_fields VALUES (8, 'mobile', 'mobile', 'Mobile Phone');
INSERT INTO bab_dbdir_fields VALUES (9, 'htel', 'homephone', 'Home Phone');
INSERT INTO bab_dbdir_fields VALUES (10, 'bfax', 'facsimiletelephonenumber', 'Business Fax');
INSERT INTO bab_dbdir_fields VALUES (11, 'title', 'title', 'Title');
INSERT INTO bab_dbdir_fields VALUES (12, 'departmentnumber', 'departmentnumber', 'Department');
INSERT INTO bab_dbdir_fields VALUES (13, 'organisationname', 'o', 'Company');
INSERT INTO bab_dbdir_fields VALUES (14, 'bstreetaddress', 'street', 'Business Street');
INSERT INTO bab_dbdir_fields VALUES (15, 'bcity', 'l', 'Business City');
INSERT INTO bab_dbdir_fields VALUES (16, 'bpostalcode', 'postalcode', 'Business Postal Code');
INSERT INTO bab_dbdir_fields VALUES (17, 'bstate', 'st', 'Business State');
INSERT INTO bab_dbdir_fields VALUES (18, 'bcountry', 'st', 'Business Country');
INSERT INTO bab_dbdir_fields VALUES (19, 'hstreetaddress', 'homepostaladdress', 'Home Street');
INSERT INTO bab_dbdir_fields VALUES (20, 'hcity', '', 'Home City');
INSERT INTO bab_dbdir_fields VALUES (21, 'hpostalcode', '', 'Home Postal Code');
INSERT INTO bab_dbdir_fields VALUES (22, 'hstate', '', 'Home State');
INSERT INTO bab_dbdir_fields VALUES (23, 'hcountry', '', 'Home Country');
INSERT INTO bab_dbdir_fields VALUES (24, 'user1', '', 'User 1');
INSERT INTO bab_dbdir_fields VALUES (25, 'user2', '', 'User 2');
INSERT INTO bab_dbdir_fields VALUES (26, 'user3', '', 'User 3');

#
# Structure de la table `bab_dbdir_fieldsextra`
#

CREATE TABLE bab_dbdir_fieldsextra (
  id int(11) unsigned NOT NULL auto_increment,
  id_directory int(11) unsigned NOT NULL default '0',
  id_field int(11) unsigned NOT NULL default '0',
  default_value text NOT NULL,
  modifiable enum('N','Y') NOT NULL default 'N',
  required enum('N','Y') NOT NULL default 'N',
  multilignes enum('N','Y') NOT NULL default 'N',
  ordering int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_directory (id_directory)
);


INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 1, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 2, '', 'Y', 'Y', 'N', 1);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 3, '', 'Y', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 4, '', 'Y', 'Y', 'N', 2);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 5, '', 'Y', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 6, '', 'Y', 'Y', 'N', 3);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 7, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 8, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 9, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 10, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 11, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 12, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 13, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 14, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 15, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 16, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 17, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 18, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 19, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 20, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 21, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 22, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 23, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 24, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 25, '', 'N', 'N', 'N', 0);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 26, '', 'N', 'N', 'N', 0);


#
# Structure de la table `bab_dbdiradd_groups`
#

CREATE TABLE bab_dbdiradd_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_dbdirupdate_groups`
#

CREATE TABLE bab_dbdirupdate_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_dbdirview_groups`
#

CREATE TABLE bab_dbdirview_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table `bab_ldap_directories`
#

CREATE TABLE bab_ldap_directories (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  host tinytext NOT NULL,
  basedn text NOT NULL,
  userdn text NOT NULL,
  password tinyblob NOT NULL,
  id_dgowner int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_dgowner (id_dgowner)
);

#
# Structure de la table `bab_ldapdirview_groups`
#

CREATE TABLE bab_ldapdirview_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_vac_coll_types`
#

CREATE TABLE bab_vac_coll_types (
  id int(11) unsigned NOT NULL auto_increment,
  id_coll int(11) unsigned NOT NULL default '0',
  id_type int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_coll (id_coll),
  KEY id_type (id_type)
);

#
# Structure de la table `bab_vac_collections`
#

CREATE TABLE bab_vac_collections (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(25) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
);

#
# Structure de la table `bab_vac_entries`
#

CREATE TABLE bab_vac_entries (
  id int(11) unsigned NOT NULL auto_increment,
  id_user int(11) unsigned NOT NULL default '0',
  date_begin date NOT NULL default '0000-00-00',
  date_end date NOT NULL default '0000-00-00',
  day_begin tinyint(3) unsigned NOT NULL default '0',
  day_end tinyint(3) unsigned NOT NULL default '0',
  idfai int(11) unsigned NOT NULL default '0',
  comment tinytext NOT NULL,
  date date NOT NULL default '0000-00-00',
  status char(1) NOT NULL default '',
  comment2 tinytext NOT NULL,
  id_approver int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY date (date),
  KEY id_user (id_user),
  KEY idfai (idfai),
  KEY date_begin (date_begin),
  KEY date_end (date_end)
);

#
# Structure de la table `bab_vac_entries_elem`
#

CREATE TABLE bab_vac_entries_elem (
  id int(11) unsigned NOT NULL auto_increment,
  id_entry int(11) unsigned NOT NULL default '0',
  id_type int(11) unsigned NOT NULL default '0',
  quantity decimal(3,1) NOT NULL default '0.0',
  PRIMARY KEY  (id),
  KEY id_entry (id_entry),
  KEY id_type (id_type)
);

#
# Structure de la table `bab_vac_managers`
#

CREATE TABLE bab_vac_managers (
  id int(11) unsigned NOT NULL auto_increment,
  id_user int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_user (id_user)
);

#
# Structure de la table `bab_vac_personnel`
#

CREATE TABLE bab_vac_personnel (
  id int(11) unsigned NOT NULL auto_increment,
  id_user int(11) unsigned NOT NULL default '0',
  id_coll int(11) unsigned NOT NULL default '0',
  id_sa int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_user (id_user),
  KEY id_coll (id_coll),
  KEY id_sa (id_sa)
);

#
# Structure de la table `bab_vac_rights`
#

CREATE TABLE bab_vac_rights (
  id int(11) unsigned NOT NULL auto_increment,
  id_creditor int(11) unsigned NOT NULL default '0',
  date_entry date NOT NULL default '0000-00-00',
  date_begin date NOT NULL default '0000-00-00',
  date_end date NOT NULL default '0000-00-00',
  quantity tinyint(3) unsigned NOT NULL default '0',
  id_type int(11) unsigned NOT NULL default '0',
  description varchar(255) NOT NULL default '',
  active enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (id),
  KEY id_type (id_type),
  KEY date_entry (date_entry)
);

#
# Structure de la table `bab_vac_types`
#

CREATE TABLE bab_vac_types (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(20) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  quantity decimal(3,1) NOT NULL default '0.0',
  maxdays decimal(3,1) NOT NULL default '0.0',
  mindays decimal(3,1) NOT NULL default '0.0',
  defaultdays decimal(3,1) NOT NULL default '0.0',
  PRIMARY KEY  (id)
);

#
# Structure de la table `bab_vac_users_rights`
#

CREATE TABLE bab_vac_users_rights (
  id int(11) unsigned NOT NULL auto_increment,
  id_user int(11) unsigned NOT NULL default '0',
  id_right int(11) unsigned NOT NULL default '0',
  quantity char(5) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_user (id_user),
  KEY id_right (id_right)
);

#
# Structure de la table `bab_fm_fields`
#

CREATE TABLE bab_fm_fields (
  id int(11) unsigned NOT NULL auto_increment,
  id_folder int(11) unsigned NOT NULL default '0',
  name char(255) NOT NULL default '',
  defaultval char(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_folder (id_folder)
);

#
# Structure de la table `bab_fm_fieldsval`
#

CREATE TABLE bab_fm_fieldsval (
  id int(11) unsigned NOT NULL auto_increment,
  id_field int(11) unsigned NOT NULL default '0',
  id_file int(11) unsigned NOT NULL default '0',
  fvalue char(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_file (id_file),
  KEY id_field (id_field)
);

#
# Structure de la table `bab_fm_filesver`
#

CREATE TABLE bab_fm_filesver (
  id int(11) unsigned NOT NULL auto_increment,
  id_file int(11) unsigned NOT NULL default '0',
  date datetime NOT NULL default '0000-00-00 00:00:00',
  author int(11) unsigned NOT NULL default '0',
  ver_major smallint(5) unsigned NOT NULL default '1',
  ver_minor smallint(5) unsigned NOT NULL default '0',
  comment tinytext NOT NULL,
  idfai int(11) unsigned NOT NULL default '0',
  confirmed enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (id),
  KEY id_file (id_file)
); 

#
# Structure de la table `bab_fm_fileslog`
#

CREATE TABLE bab_fm_fileslog (
  id int(11) unsigned NOT NULL auto_increment,
  id_file int(11) unsigned NOT NULL default '0',
  date datetime NOT NULL default '0000-00-00 00:00:00',
  author int(11) unsigned NOT NULL default '0',
  action smallint(5) unsigned NOT NULL default '0',
  comment tinytext NOT NULL,
  version varchar(10) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_file (id_file)
);

#
# Structure de la table `bab_dg_groups`
#

CREATE TABLE bab_dg_groups (
  id int(11) unsigned NOT NULL auto_increment,
  name char(255) NOT NULL default '',
  description char(255) NOT NULL default '',
  groups enum('N','Y') NOT NULL default 'N',
  sections enum('N','Y') NOT NULL default 'N',
  articles enum('N','Y') NOT NULL default 'N',
  faqs enum('N','Y') NOT NULL default 'N',
  forums enum('N','Y') NOT NULL default 'N',
  calendars enum('N','Y') NOT NULL default 'N',
  mails enum('N','Y') NOT NULL default 'N',
  directories enum('N','Y') NOT NULL default 'N',
  approbations enum('N','Y') NOT NULL default 'N',
  filemanager enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (id)
);

#
# Structure de la table `bab_dg_users_groups`
#

CREATE TABLE bab_dg_users_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table `bab_org_charts`
#

CREATE TABLE bab_org_charts (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  isprimary enum('N','Y') NOT NULL default 'N',
  edit enum('N','Y') NOT NULL default 'N',
  edit_author int(11) unsigned NOT NULL default '0',
  edit_date datetime NOT NULL default '0000-00-00 00:00:00',
  id_dgowner int(11) unsigned NOT NULL default '0',
  id_directory int(11) unsigned NOT NULL default '0',
  type smallint(5) unsigned NOT NULL default '0',
  id_first_node int(11) unsigned NOT NULL default '0',
  id_closed_nodes text NOT NULL,
  PRIMARY KEY  (id),
  KEY id_dgowner (id_dgowner),
  KEY id_directory (id_directory)
);

INSERT INTO bab_org_charts VALUES (1, 'Ovidentia', 'Ovidentia organizational chart', 'Y', 'N', 0, '0000-00-00 00:00:00', 0, 1, 0, 0, '');

#
# Structure de la table `bab_ocupdate_groups`
#

CREATE TABLE bab_ocupdate_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table `bab_ocview_groups`
#

CREATE TABLE bab_ocview_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_oc_entities`
#

CREATE TABLE bab_oc_entities (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  id_oc int(11) unsigned NOT NULL default '0',
  id_node int(11) unsigned NOT NULL default '0',
  e_note text NOT NULL,
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_oc (id_oc),
  KEY id_node (id_node),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_oc_roles`
#

CREATE TABLE bab_oc_roles (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(225) NOT NULL default '',
  description tinytext NOT NULL,
  id_oc int(11) unsigned NOT NULL default '0',
  id_entity int(11) NOT NULL default '0',
  type tinyint(3) unsigned NOT NULL default '0',
  cardinality enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (id),
  KEY id_oc (id_oc),
  KEY type (type),
  KEY id_entity (id_entity)
);

#
# Structure de la table `bab_oc_roles_users`
#

CREATE TABLE bab_oc_roles_users (
  id int(11) unsigned NOT NULL auto_increment,
  id_role int(11) unsigned NOT NULL default '0',
  id_user int(11) unsigned NOT NULL default '0',
  isprimary enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (id),
  KEY id_role (id_role),
  KEY id_user (id_user),
  KEY isprimary (isprimary)
);

#
# Structure de la table `bab_oc_trees`
#

CREATE TABLE bab_oc_trees (
  id int(11) unsigned NOT NULL auto_increment,
  lf int(11) unsigned NOT NULL default '0',
  lr int(11) unsigned NOT NULL default '0',
  id_parent int(11) unsigned NOT NULL default '0',
  id_user int(11) unsigned NOT NULL default '0',
  info_user varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY lf (lf),
  KEY lr (lr),
  KEY id_parent (id_parent),
  KEY id_user (id_user),
  KEY info_user (info_user)
);

#
# Structure de la table `bab_faq_subcat`
#

CREATE TABLE bab_faq_subcat (
  id int(11) unsigned NOT NULL auto_increment,
  id_cat int(11) unsigned NOT NULL default '0',
  name text NOT NULL,
  id_node int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_cat (id_cat,id_node),
  KEY id_node (id_node)
);

#
# Structure de la table `bab_faq_trees`
#

CREATE TABLE bab_faq_trees (
  id int(11) unsigned NOT NULL auto_increment,
  lf int(11) unsigned NOT NULL default '0',
  lr int(11) unsigned NOT NULL default '0',
  id_parent int(11) unsigned NOT NULL default '0',
  id_user int(11) unsigned NOT NULL default '0',
  info_user varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY lf (lf),
  KEY lr (lr),
  KEY id_parent (id_parent),
  KEY id_user (id_user),
  KEY info_user (info_user)
);

#
# Structure de la table `bab_ldap_sites_fields`
#

CREATE TABLE bab_ldap_sites_fields (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  x_name varchar(255) NOT NULL default '',
  id_site int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY name (name),
  KEY id_site (id_site)
);


#
# Structure de la table `bab_art_drafts`
#

CREATE TABLE bab_art_drafts (
  id int(11) unsigned NOT NULL auto_increment,
  id_author int(11) unsigned NOT NULL default '0',
  date_creation datetime NOT NULL default '0000-00-00 00:00:00',
  date_modification datetime NOT NULL default '0000-00-00 00:00:00',
  date_submission datetime NOT NULL default '0000-00-00 00:00:00',
  date_publication datetime NOT NULL default '0000-00-00 00:00:00',
  date_archiving datetime NOT NULL default '0000-00-00 00:00:00',
  title tinytext NOT NULL,
  head mediumtext NOT NULL,
  body longtext NOT NULL,
  lang varchar(10) NOT NULL default '',
  trash enum('N','Y') NOT NULL default 'N',
  id_topic int(11) unsigned NOT NULL default '0',
  restriction varchar(255) NOT NULL default '',
  hpage_private enum('N','Y') NOT NULL default 'N',
  hpage_public enum('N','Y') NOT NULL default 'N',
  notify_members enum('Y','N') NOT NULL default 'N',
  idfai int(11) unsigned NOT NULL default '0',
  result smallint(5) unsigned NOT NULL default '0',
  id_article int(11) unsigned NOT NULL default '0',
  id_anonymous int(11) unsigned NOT NULL default '0',
  approbation enum('0','1','2') NOT NULL default '0',
  update_datemodif enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (id),
  KEY id_topic (id_topic),
  KEY id_author (id_author),
  KEY trash (trash),
  KEY result (result)
);

#
# Structure de la table bab_art_drafts_files
#

CREATE TABLE bab_art_drafts_files (
  id int(11) unsigned NOT NULL auto_increment,
  id_draft int(11) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_draft (id_draft)
);

#
# Structure de la table `bab_art_drafts_notes`
#

CREATE TABLE bab_art_drafts_notes (
  id int(11) unsigned NOT NULL auto_increment,
  id_draft int(11) unsigned NOT NULL default '0',
  content text NOT NULL,
  id_author int(11) unsigned NOT NULL default '0',
  date_note datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY id_draft (id_draft),
  KEY id_author (id_author)
);

#
# Structure de la table `bab_art_files`
#

CREATE TABLE bab_art_files (
  id int(11) unsigned NOT NULL auto_increment,
  id_article int(11) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_article (id_article)
);


#
# Structure de la table `bab_art_log`
#

CREATE TABLE bab_art_log (
  id int(11) unsigned NOT NULL auto_increment,
  id_article int(11) unsigned NOT NULL default '0',
  id_author int(11) unsigned NOT NULL default '0',
  date_log datetime NOT NULL default '0000-00-00 00:00:00',
  action_log enum('lock','unlock','commit','refused','accepted') NOT NULL default 'lock',
  art_log text NOT NULL,
  PRIMARY KEY  (id),
  KEY id_article (id_article),
  KEY id_author (id_author)
);


#
# Structure de la table `bab_topicsman_groups`
#

CREATE TABLE bab_topicsman_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_topicsmod_groups`
#

CREATE TABLE bab_topicsmod_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table `bab_forumsman_groups`
#

CREATE TABLE bab_forumsman_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table `bab_sites_fields_registration`
#

CREATE TABLE bab_sites_fields_registration (
  id int(11) unsigned NOT NULL auto_increment,
  id_site tinyint(2) unsigned NOT NULL default '0',
  id_field int(11) unsigned NOT NULL default '0',
  registration enum('N','Y') NOT NULL default 'N',
  required enum('N','Y') NOT NULL default 'N',
  multilignes enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (id),
  KEY id_site (id_site)
);

INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 1, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 2, 'Y', 'Y', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 3, 'Y', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 4, 'Y', 'Y', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 5, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 6, 'Y', 'Y', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 7, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 8, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 9, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 10, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 11, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 12, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 13, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 14, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 15, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 16, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 17, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 18, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 19, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 20, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 21, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 22, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 23, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 24, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 25, 'N', 'N', 'N');
INSERT INTO bab_sites_fields_registration (id_site, id_field, registration, required, multilignes ) VALUES (1, 26, 'N', 'N', 'N');

#
# Structure de la table bab_sites_disclaimers
#

CREATE TABLE bab_sites_disclaimers (
	id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	id_site TINYINT( 2 ) UNSIGNED NOT NULL ,
	disclaimer_text LONGTEXT NOT NULL ,
	PRIMARY KEY ( id ) ,
	KEY id_site (id_site)
);

INSERT INTO bab_sites_disclaimers (id_site, disclaimer_text ) VALUES (1, '');

#
# Structure de la table `bab_profiles`
#

CREATE TABLE bab_profiles (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  multiplicity enum('Y','N') NOT NULL default 'Y',
  inscription enum('N','Y') NOT NULL default 'N',
  id_dgowner int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_dgowner (id_dgowner)
);


#
# Structure de la table `bab_profiles_groups`
#

CREATE TABLE bab_profiles_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table `bab_profiles_groupsset`
#

CREATE TABLE bab_profiles_groupsset (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


