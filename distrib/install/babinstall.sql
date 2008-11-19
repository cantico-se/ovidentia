# phpMyAdmin MySQL-Dump
# http://phpwizard.net/phpMyAdmin/
#
# Serveur: localhost Base de donn�es: ovidentia

# --------------------------------------------------------
#
# Structure de la table 'bab_articles'
#

CREATE TABLE bab_articles (
  id int(11) unsigned NOT NULL auto_increment,
  id_topic int(11) unsigned NOT NULL default '0',
  id_author int(11) unsigned NOT NULL default '0',
  `date` datetime default NULL,
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
   id_author int(11) unsigned DEFAULT '0' NOT NULL,
   `date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
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


#
# Structure de la table `bab_faqmanagers_groups`
#

CREATE TABLE bab_faqmanagers_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
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
   date_modification datetime NOT NULL default '0000-00-00 00:00:00',
   id_modifiedby int(11) unsigned NOT NULL default '0',
   PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_forums'
#

CREATE TABLE bab_forums (
   id smallint(6) unsigned NOT NULL auto_increment,
   name varchar(30) NOT NULL,
   description text NOT NULL,
   moderation enum('N','Y') DEFAULT 'N' NOT NULL,
   notification enum('N','Y') DEFAULT 'N' NOT NULL,
   display int(11) unsigned DEFAULT '0' NOT NULL,
   active enum('Y','N') DEFAULT 'Y' NOT NULL,
   ordering smallint(6) unsigned NOT NULL default '0',
   id_dgowner int(11) unsigned NOT NULL default '0',
   nb_recipients smallint(2) unsigned NOT NULL default '0',
   bdisplayemailaddress enum('N','Y') DEFAULT 'N' NOT NULL,
   bdisplayauhtordetails enum('N','Y') DEFAULT 'N' NOT NULL,
   bflatview enum('Y','N') DEFAULT 'Y' NOT NULL,
   bupdatemoderator enum('Y','N') DEFAULT 'Y' NOT NULL,
   bupdateauthor enum('N','Y') DEFAULT 'N' NOT NULL,
   PRIMARY KEY  (id),
   KEY id_dgowner (id_dgowner)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_forumsfiles'
#


CREATE TABLE bab_forumsfiles (
  id int(10) unsigned NOT NULL auto_increment,
  id_post int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  description tinytext NOT NULL,
  index_status tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_post (id_post)
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


CREATE TABLE `bab_groups` (
  `id` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `mail` enum('N','Y') default NULL,
  `manager` int(11) unsigned NOT NULL default '0',
  `ustorage` enum('N','Y') default NULL,
  `notes` enum('Y','N') default NULL,
  `contacts` enum('Y','N') default NULL,
  `directory` enum('N','Y') default NULL,
  `pcalendar` enum('Y','N') default NULL,
  `id_ocentity` int(11) unsigned default '0',
  `id_parent` int(10) unsigned default NULL,
  `lf` int(10) unsigned NOT NULL default '0',
  `lr` int(10) unsigned NOT NULL default '0',
  `nb_set` int(10) unsigned default NULL,
  `nb_groups` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `manager` (`manager`),
  KEY `id_parent` (`id_parent`,`lf`,`lr`)
);

INSERT INTO bab_groups (id, name, description, mail, ustorage, notes, contacts, directory, pcalendar, id_parent, lf, lr, nb_set) VALUES ( '0', 'Ovidentia users', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', '8', '0');
INSERT INTO bab_groups (id, name, description, mail, ustorage, notes, contacts, directory, pcalendar, id_parent, lf, lr, nb_set) VALUES ( '1', 'Registered users', 'All registered users', 'N', 'N', 'Y', 'Y', 'Y', 'Y', '0', '2', '5', '0');
INSERT INTO bab_groups (id, name, description, mail, ustorage, notes, contacts, directory, pcalendar, id_parent, lf, lr, nb_set) VALUES ( '2', 'Anonymous users', 'all not registered users', NULL, NULL, NULL, NULL, NULL, NULL, '0', '6', '7', '0');
INSERT INTO bab_groups (id, name, description, mail, ustorage, notes, contacts, directory, pcalendar, id_parent, lf, lr, nb_set) VALUES ( '3', 'Administrators', 'Manage the site', 'N', 'N', 'Y', 'Y', 'N', 'Y', '1', '3', '4', '0');





# --------------------------------------------------------
#
# Structure de la table 'bab_groups_set_assoc'
#

CREATE TABLE `bab_groups_set_assoc` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_group` int(10) unsigned NOT NULL default '0',
  `id_set` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `id_group` (`id_group`,`id_set`)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_notes'
#

CREATE TABLE bab_notes (
   id int(11) unsigned NOT NULL auto_increment,
   id_user int(11) unsigned DEFAULT '0' NOT NULL,
   `date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
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
   `date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   dateupdate datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   date_confirm datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   author text NOT NULL,
   id_author int(11) unsigned DEFAULT '0' NOT NULL,
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
INSERT INTO bab_private_sections VALUES ('5', '1', 'User''s section', 'This section is for User', 'Y', 'N');

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
   `date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
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
  auto_approbation enum('N','Y') NOT NULL default 'N',
  busetags enum('N','Y') NOT NULL default 'N',
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
   `date` datetime DEFAULT '0000-00-00 00:00:00',
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
   date_longformat varchar(255) NOT NULL default '',
   date_shortformat varchar(255) NOT NULL default '',
   time_format varchar(255) NOT NULL default '',
   db_authentification enum('N','Y') DEFAULT 'N' NOT NULL,
   cookie_validity datetime DEFAULT '0000-00-00 00:00:00',
   cookie_id varchar(255),
   id_sitemap_profile int(11) unsigned NOT NULL,
   PRIMARY KEY (id),
   KEY nickname (nickname),
   KEY firstname (firstname),
   KEY lastname (lastname),
   KEY hashname (hashname)
);

INSERT INTO bab_users VALUES ( '1', 'admin@admin.bab', 'Administrator', '', '200ceb26807d6bf99fd6f4f0d1ca54d4', 'admin@admin.bab', '2001-04-03 00:00:00', '22975d8a5ed1b91445f6c55ac121505b', '1', '', '0da8f2a37b9e7926e08196a6bd1baa29', '1', '0', '', '', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0', '', '', '','N','0000-00-00 00:00:00','','0');

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

CREATE TABLE `bab_users_log` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `id_user` int(11) unsigned NOT NULL default '0',
  `dateact` timestamp NOT NULL default '0000-00-00 00:00:00',
  `sessid` tinytext NOT NULL,
  `remote_addr` varchar(255) NOT NULL default '',
  `forwarded_for` varchar(255) NOT NULL default '',
  `id_dg` int(11) unsigned NOT NULL default '0',
  `grp_change` tinyint(1) unsigned default NULL,
  `schi_change` tinyint(1) unsigned default NULL,
  `cnx_try` int(2) unsigned NOT NULL default '0',
  `cpw` varchar(255) NOT NULL default '',
  `tg` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `id_user` (`id_user`)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_categories'
#

CREATE TABLE bab_cal_categories (
	id TINYINT (2) UNSIGNED not null AUTO_INCREMENT,
	name VARCHAR (60) not null,
	description VARCHAR (255) not null,
	bgcolor VARCHAR (6) not null,
	PRIMARY KEY (id)
);


# --------------------------------------------------------
#
# Table structure for table `bab_cal_resources`
#

CREATE TABLE bab_cal_resources (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(60) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `id_dgowner` int(11) unsigned NOT NULL default '0',
  `idsa` int(11) unsigned NOT NULL default '0',
  `availability_lock` tinyint(1) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `id_dgowner` (`id_dgowner`)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_cal_events'
#

CREATE TABLE bab_cal_events (
  id int(11) unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  description text NOT NULL,
  location varchar(255) NOT NULL default '',
  start_date datetime NOT NULL default '0000-00-00 00:00:00',
  end_date datetime NOT NULL default '0000-00-00 00:00:00',
  id_cat int(11) unsigned NOT NULL default '0',
  id_creator int(11) unsigned NOT NULL default '0',
  hash varchar(34) NOT NULL default '',
  color varchar(8) NOT NULL default '',
  bprivate enum('Y','N') NOT NULL default 'N',
  block enum('Y','N') NOT NULL default 'N',
  bfree enum('Y','N') NOT NULL default 'N',
  date_modification datetime NOT NULL default '0000-00-00 00:00:00',
  id_modifiedby int(11) unsigned NOT NULL default '0',
  uuid varchar(255) NOT NULL,
  PRIMARY KEY  (id),
  KEY start_date (start_date),
  KEY end_date (end_date)
);


# --------------------------------------------------------
#
# Table structure for table `bab_cal_events_owners`
#

CREATE TABLE bab_cal_events_owners (
  id_event int(10) unsigned NOT NULL default '0',
  id_cal int(10) unsigned NOT NULL default '0',
  status tinyint(3) unsigned NOT NULL default '0',
  idfai int(11) unsigned NOT NULL default '0',
  KEY id_event (id_event,id_cal,status)
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
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `lang` varchar(10) NOT NULL default '',
  `adminemail` varchar(255) NOT NULL default '',
  `adminname` varchar(255) NOT NULL default '',
  `skin` varchar(255) NOT NULL default '',
  `style` varchar(255) NOT NULL default '',
  `registration` enum('Y','N') NOT NULL default 'Y',
  `display_disclaimer` enum('N','Y') NOT NULL default 'N',
  `email_confirm` tinyint(4) NOT NULL default '0',
  `mailfunc` varchar(20) NOT NULL default 'mail',
  `smtpserver` varchar(255) NOT NULL default '',
  `smtpport` varchar(20) NOT NULL default '25',
  `imgsize` int(11) unsigned NOT NULL default '0',
  `idgroup` int(11) unsigned NOT NULL default '0',
  `smtpuser` varchar(255) NOT NULL default '',
  `smtppassword` tinyblob NOT NULL default '',
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
  `change_lang` enum('Y','N') NOT NULL default 'Y',
  `change_skin` enum('Y','N') NOT NULL default 'Y',
  `change_date` enum('Y','N') NOT NULL default 'Y',
  `change_unavailability` enum('Y','N') NOT NULL default 'Y',
  `name_order` enum('F L','L F') NOT NULL default 'F L',
  `email_password` enum('Y','N') NOT NULL default 'Y',
  `browse_users` enum('N','Y') NOT NULL default 'N',
  `authentification` smallint(5) unsigned NOT NULL default '0',
  `ldap_host` tinytext NOT NULL default '',
  `ldap_domainname` varchar(255) NOT NULL default '',
  `ldap_userdn` text NOT NULL default '',
  `ldap_admindn` text NOT NULL default '',
  `ldap_adminpassword` tinyblob NOT NULL default '',
  `ldap_searchdn` text NOT NULL default '',
  `ldap_attribute` text NOT NULL default '',
  `ldap_filter` text NOT NULL default '',
  `ldap_allowadmincnx` enum('Y','N') NOT NULL default 'Y',
  `ldap_encryptiontype` varchar(255) NOT NULL default '',
  `ldap_decoding_type` tinyint(1) unsigned NOT NULL default '0',
  `ldap_notifyadministrators` enum('N','Y') NOT NULL default 'N',
  `date_longformat` varchar(255) NOT NULL default '',
  `date_shortformat` varchar(255) NOT NULL default '',
  `time_format` varchar(255) NOT NULL default '',
  `stat_update_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `dispdays` varchar(20) NOT NULL default '',
  `startday` tinyint(4) NOT NULL default '1',
  `user_workdays` enum('Y','N') NOT NULL default 'Y',
  `elapstime` tinyint(2) unsigned NOT NULL default '30',
  `defaultview` tinyint(3) NOT NULL default '0',
  `start_time` time NOT NULL default '08:00:00',
  `end_time` time NOT NULL default '18:00:00',
  `allday` enum('Y','N') NOT NULL default 'Y',
  `usebgcolor` enum('Y','N') NOT NULL default 'Y',
  `stat_log` enum('Y','N') NOT NULL default 'N',
  `show_update_info` enum('Y','N') NOT NULL default 'Y',
  `iDefaultCalendarAccess` SMALLINT( 2 ) NOT NULL DEFAULT '-1',
  `mail_fieldaddress` char(3) NOT NULL default 'Bcc',
  `mail_maxperpacket` smallint(2) unsigned NOT NULL default '25',
  `mass_mailing` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
);


INSERT INTO bab_sites (id, name, description, lang, adminemail,  adminname, skin, style, dispdays, startday ) values ('1', 'Ovidentia', 'Ovidentia site', 'en', 'admin@your-domain.com', 'Ovidentia Administrator', 'ovidentia_sw', 'ovidentia.css', '1,2,3,4,5','1');


# --------------------------------------------------------
#
# Structure de la table 'bab_sites_hpman_groups'
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
  index_status tinyint(1) unsigned NOT NULL default '0',
  iIdDgOwner int(11) unsigned NOT NULL,
  PRIMARY KEY  (id),
  KEY id_owner (id_owner),
  KEY index_status (index_status),
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
INSERT INTO bab_mime_types VALUES ('pdf', 'application/pdf');
INSERT INTO bab_mime_types VALUES ('sxw', 'application/vnd.sun.xml.writer');
INSERT INTO bab_mime_types VALUES ('odt', 'application/vnd.oasis.opendocument.text');
INSERT INTO bab_mime_types VALUES ('ods', 'application/vnd.oasis.opendocument.spreadsheet');
INSERT INTO bab_mime_types VALUES ('odp', 'application/vnd.oasis.opendocument.presentation');
INSERT INTO bab_mime_types VALUES ('odc', 'application/vnd.oasis.opendocument.chart');
INSERT INTO bab_mime_types VALUES ('odf', 'application/vnd.oasis.opendocument.formula');
INSERT INTO bab_mime_types VALUES ('odb', 'application/vnd.oasis.opendocument.database');
INSERT INTO bab_mime_types VALUES ('odi', 'application/vnd.oasis.opendocument.image');
INSERT INTO bab_mime_types VALUES ('odm', 'application/vnd.oasis.opendocument.text-master');
INSERT INTO bab_mime_types VALUES ('ott', 'application/vnd.oasis.opendocument.text-template');
INSERT INTO bab_mime_types VALUES ('ots', 'application/vnd.oasis.opendocument.spreadsheet-template');
INSERT INTO bab_mime_types VALUES ('otp', 'application/vnd.oasis.opendocument.presentation-template');
INSERT INTO bab_mime_types VALUES ('otg', 'application/vnd.oasis.opendocument.graphics-template');


# --------------------------------------------------------
#
# Structure de la table 'bab_addons'
#

CREATE TABLE `bab_addons` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `enabled` enum('Y','N') NOT NULL default 'Y',
  `version` varchar(127) NOT NULL default '',
  `installed` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`id`),
  KEY `installed` (`installed`),
  KEY `enabled` (`enabled`)
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
  id_oc int(11) unsigned NOT NULL default '0',
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
  far_order int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
);


#
# Structure de la table `bab_fm_folders`
#

CREATE TABLE bab_fm_folders (
  id int(11) unsigned NOT NULL auto_increment,
  folder char(255) NOT NULL default '',
  sRelativePath text NOT NULL,
  manager int(11) unsigned NOT NULL default '0',
  idsa int(11) unsigned NOT NULL default '0',
  filenotify enum('N','Y') NOT NULL default 'N',
  active enum('Y','N') NOT NULL default 'Y',
  version enum('N','Y') NOT NULL default 'N',
  id_dgowner int(11) unsigned NOT NULL default '0',
  bhide enum('N','Y') NOT NULL default 'N',
  auto_approbation enum('N','Y') NOT NULL default 'N',
  baddtags enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (id),
  KEY folder (folder),
  KEY id_dgowner (id_dgowner)
);

#
# Structure de la table `bab_fm_folders_clipboard`
#

CREATE TABLE bab_fm_folders_clipboard (
  `iId` int(11) unsigned NOT NULL auto_increment,
  `iIdDgOwner` int(11) unsigned NOT NULL,
  `iIdRootFolder` int(11) unsigned NOT NULL,
  `iIdFolder` int(11) unsigned NOT NULL,
  `sName`  varchar(255) NOT NULL,
  `sRelativePath` TEXT NOT NULL,
  `sGroup` ENUM('Y','N') NOT NULL,
  `sCollective` ENUM('Y','N') NOT NULL,
  `iIdOwner` int(11) unsigned NOT NULL,
  `sCheckSum` CHAR( 32 ) NOT NULL,
  PRIMARY KEY  (`iId`),
  UNIQUE `sFolder` (`sGroup`, `sCollective`, `sCheckSum`, `iIdOwner`),
  KEY `iIdDgOwner` (`iIdDgOwner`),
  KEY `iIdFolder` (`iIdFolder`),
  KEY `sCollective` (`sCollective`)
);

#
# Structure de la table `bab_fmmanagers_groups`
#

CREATE TABLE bab_fmmanagers_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
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
  show_update_info enum('N','Y') NOT NULL default 'N',
  ovml_list tinytext NOT NULL default '',
  ovml_detail tinytext NOT NULL default '',
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
  bstreetaddress text NOT NULL default '',
  bcity varchar(255) NOT NULL default '',
  bpostalcode varchar(10) NOT NULL default '',
  bstate varchar(255) NOT NULL default '',
  bcountry varchar(255) NOT NULL default '',
  hstreetaddress text NOT NULL,
  hcity varchar(255) NOT NULL default '',
  hpostalcode varchar(10) NOT NULL default '',
  hstate varchar(255) NOT NULL default '',
  hcountry varchar(255) NOT NULL default '',
  user1 text NOT NULL default '',
  user2 text NOT NULL default '',
  user3 text NOT NULL default '',
  photo_data longblob NOT NULL default '',
  photo_type varchar(20) NOT NULL default '',
  id_directory int(11) unsigned NOT NULL default '0',
  id_user int(11) unsigned NOT NULL default '0',
  date_modification datetime NOT NULL default '0000-00-00 00:00:00',
  id_modifiedby int(11) unsigned NOT NULL default '0',
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
  default_value int(11) unsigned NOT NULL default '0',
  modifiable enum('N','Y') NOT NULL default 'N',
  required enum('N','Y') NOT NULL default 'N',
  multilignes enum('N','Y') NOT NULL default 'N',
  disabled enum('N','Y') NOT NULL default 'N',
  multi_values enum('N','Y') NOT NULL default 'N',
  ordering int(11) unsigned NOT NULL default '0',
  list_ordering int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_directory (id_directory)
);


INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 1, 0, 'N', 'N', 'N', 0, 1);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 2, 0, 'Y', 'Y', 'N', 1, 2);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 3, 0, 'Y', 'N', 'N', 0, 3);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 4, 0, 'Y', 'Y', 'N', 2, 4);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 5, 0, 'Y', 'N', 'N', 0, 5);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 6, 0, 'Y', 'Y', 'N', 3, 6);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 7, 0, 'N', 'N', 'N', 0, 7);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 8, 0, 'N', 'N', 'N', 0, 8);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 9, 0, 'N', 'N', 'N', 0, 9);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 10, 0, 'N', 'N', 'N', 0, 10);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 11, 0, 'N', 'N', 'N', 0, 11);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 12, 0, 'N', 'N', 'N', 0, 12);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 13, 0, 'N', 'N', 'N', 0, 13);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 14, 0, 'N', 'N', 'N', 0, 14);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 15, 0, 'N', 'N', 'N', 0, 15);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 16, 0, 'N', 'N', 'N', 0, 16);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 17, 0, 'N', 'N', 'N', 0, 17);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 18, 0, 'N', 'N', 'N', 0, 18);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 19, 0, 'N', 'N', 'N', 0, 19);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 20, 0, 'N', 'N', 'N', 0, 20);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 21, 0, 'N', 'N', 'N', 0, 21);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 22, 0, 'N', 'N', 'N', 0, 22);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 23, 0, 'N', 'N', 'N', 0, 23);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 24, 0, 'N', 'N', 'N', 0, 24);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 25, 0, 'N', 'N', 'N', 0, 25);
INSERT INTO bab_dbdir_fieldsextra (id_directory, id_field, default_value, modifiable, required, multilignes, ordering, list_ordering) VALUES (0, 26, 0, 'N', 'N', 'N', 0, 26);


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
# Structure de la table `bab_dbdir_options`
#

CREATE TABLE `bab_dbdir_options` (
  `search_view_fields` varchar(255) NOT NULL default '2,4'
);


#
# Structure de la table `bab_ldap_directories`
#

CREATE TABLE bab_ldap_directories (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  server_type tinyint(1) unsigned NOT NULL default '0',
  decoding_type tinyint(1) unsigned NOT NULL default '0',
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
  id_cat int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_cat (id_cat)
);

#
# Structure de la table `bab_vac_entries`
#

CREATE TABLE bab_vac_entries (
  id int(11) unsigned NOT NULL auto_increment,
  id_user int(11) unsigned NOT NULL default '0',
  date_begin datetime NOT NULL default '0000-00-00 00:00:00',
  date_end datetime NOT NULL default '0000-00-00 00:00:00',
  idfai int(11) unsigned NOT NULL default '0',
  comment tinytext NOT NULL,
  `date` date NOT NULL default '0000-00-00',
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
  id_right int(11) unsigned NOT NULL default '0',
  quantity decimal(3,1) NOT NULL default '0.0',
  PRIMARY KEY  (id),
  KEY id_entry (id_entry),
  KEY id_right (id_right)
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

CREATE TABLE `bab_vac_rights` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `id_creditor` int(11) unsigned NOT NULL,
  `date_entry` date NOT NULL default '0000-00-00',
  `date_begin` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  `quantity` decimal(3,1) unsigned NOT NULL default '0.0',
  `id_type` int(11) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `active` enum('Y','N') NOT NULL default 'Y',
  `cbalance` enum('Y','N') NOT NULL default 'Y',
  `date_begin_valid` date NOT NULL default '0000-00-00',
  `date_end_valid` date NOT NULL default '0000-00-00',
  `date_end_fixed` date NOT NULL default '0000-00-00',
  `date_begin_fixed` date NOT NULL default '0000-00-00',
  `day_begin_fixed` tinyint(3) unsigned NOT NULL,
  `day_end_fixed` tinyint(3) unsigned NOT NULL,
  `no_distribution` tinyint(1) unsigned NOT NULL,
  `id_rgroup` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `id_type` (`id_type`),
  KEY `date_entry` (`date_entry`),
  KEY `id_rgroup` (`id_rgroup`)
);




CREATE TABLE `bab_vac_rights_rules` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_right` int(10) unsigned NOT NULL default '0',
  `validoverlap` tinyint(1) unsigned NOT NULL default '0',
  `trigger_nbdays_min` float NOT NULL default '0',
  `trigger_nbdays_max` float NOT NULL default '0',
  `trigger_type` int(10) unsigned NOT NULL default '0',
  `trigger_p1_begin` date NOT NULL,
  `trigger_p1_end` date NOT NULL,
  `trigger_p2_begin` date NOT NULL,
  `trigger_p2_end` date NOT NULL,
  `trigger_overlap` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `id_right` (`id_right`),
  KEY `trigger_type` (`trigger_type`)
);





CREATE TABLE `bab_vac_rights_inperiod` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_right` int(10) unsigned NOT NULL default '0',
  `period_start` date NOT NULL default '0000-00-00',
  `period_end` date NOT NULL default '0000-00-00',
  `right_inperiod` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `id_right` (`id_right`)
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
  color varchar(6) NOT NULL default '',
  cbalance enum('Y','N') NOT NULL default 'Y',
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


CREATE TABLE `bab_vac_options` (
`chart_superiors_create_request` TINYINT( 1 ) UNSIGNED NOT NULL
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
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  author int(11) unsigned NOT NULL default '0',
  ver_major smallint(5) unsigned NOT NULL default '1',
  ver_minor smallint(5) unsigned NOT NULL default '0',
  comment tinytext NOT NULL,
  idfai int(11) unsigned NOT NULL default '0',
  confirmed enum('N','Y') NOT NULL default 'N',
  index_status tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_file (id_file)
); 

#
# Structure de la table `bab_fm_fileslog`
#

CREATE TABLE bab_fm_fileslog (
  id int(11) unsigned NOT NULL auto_increment,
  id_file int(11) unsigned NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
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

CREATE TABLE `bab_dg_groups` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` char(255) NOT NULL default '',
  `description` char(255) NOT NULL default '',
  `color` varchar(8) NOT NULL default '',
  `battach` enum('N','Y') NOT NULL default 'N',
  `users` enum('N','Y') NOT NULL default 'N',
  `groups` enum('N','Y') NOT NULL default 'N',
  `sections` enum('N','Y') NOT NULL default 'N',
  `articles` enum('N','Y') NOT NULL default 'N',
  `faqs` enum('N','Y') NOT NULL default 'N',
  `forums` enum('N','Y') NOT NULL default 'N',
  `calendars` enum('N','Y') NOT NULL default 'N',
  `mails` enum('N','Y') NOT NULL default 'N',
  `directories` enum('N','Y') NOT NULL default 'N',
  `approbations` enum('N','Y') NOT NULL default 'N',
  `filemanager` enum('N','Y') NOT NULL default 'N',
  `orgchart` enum('N','Y') NOT NULL default 'N',
  `taskmanager` enum('N','Y') NOT NULL default 'N',
  `id_group` int(10) unsigned default NULL,
  `iIdCategory` tinyint(2) unsigned not null default '0',
  PRIMARY KEY  (`id`),
  KEY `id_group` (`id_group`)
);

#
# Structure de la table `bab_dg_admin`
#

CREATE TABLE `bab_dg_admin` (
  `id_user` int(10) unsigned NOT NULL default '0',
  `id_dg` int(10) unsigned NOT NULL default '0',
  KEY `id_user` (`id_user`)
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
  ovml_detail tinytext NOT NULL default '',
  ovml_embedded tinytext NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_dgowner (id_dgowner),
  KEY id_directory (id_directory)
);

INSERT INTO bab_org_charts VALUES (1, 'Ovidentia', 'Ovidentia organizational chart', 'Y', 'N', 0, '0000-00-00 00:00:00', 0, 1, 0, 0, '', '');

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
# Structure de la table `bab_oc_entity_types`
#

CREATE TABLE bab_oc_entity_types (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  id_oc int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_oc (id_oc)
);

#
# Structure de la table `bab_oc_entities_entity_types`
#

CREATE TABLE bab_oc_entities_entity_types (
  id_entity int(11) unsigned NOT NULL,
  id_entity_type int(11) unsigned NOT NULL
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
  id_field int(11) unsigned NOT NULL default '0',
  x_name varchar(255) NOT NULL default '',
  id_site int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_field (id_field),
  KEY id_site (id_site)
);

INSERT INTO bab_ldap_sites_fields VALUES (1, 1, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (2, 2, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (3, 3, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (4, 4, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (5, 5, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (6, 6, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (7, 7, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (8, 8, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (9, 9, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (10, 10, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (11, 11, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (12, 12, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (13, 13, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (14, 14, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (15, 15, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (16, 16, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (17, 17, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (18, 18, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (19, 19, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (20, 20, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (21, 21, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (22, 22, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (23, 23, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (24, 24, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (25, 25, '', '1');
INSERT INTO bab_ldap_sites_fields VALUES (26, 26, '', '1');


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
  ordering smallint(2) NOT NULL default '0',
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
  index_status int(11) unsigned NOT NULL default '0',
  ordering smallint(2) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_article (id_article),
  KEY index_status (index_status)
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
  required enum('N','Y') NOT NULL default 'N',
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


#
# Table structure for table `bab_dbdir_fieldsvalues`
#

CREATE TABLE bab_dbdir_fieldsvalues (
  id int(11) unsigned NOT NULL auto_increment,
  id_fieldextra int(11) unsigned NOT NULL default '0',
  field_value varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_fieldextra (id_fieldextra)
);

#
# Table structure for table `bab_dbdir_fields_directory`
#

CREATE TABLE bab_dbdir_fields_directory (
  id int(11) unsigned NOT NULL auto_increment,
  id_directory int(11) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_directory (id_directory)
);

#
# Table structure for table `bab_dbdir_entries_extra`
#

CREATE TABLE bab_dbdir_entries_extra (
  id int(11) unsigned NOT NULL auto_increment,
  id_fieldx int(11) unsigned NOT NULL default '0',
  id_entry int(11) unsigned NOT NULL default '0',
  field_value varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id_fieldx (id_fieldx),
  KEY id_entry (id_entry)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_statsman_groups'
#

CREATE TABLE bab_statsman_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Table structure for table `bab_stats_events`
#

CREATE TABLE bab_stats_events (
  id bigint(20) unsigned NOT NULL auto_increment,
  evt_id_site tinyint(2) unsigned NOT NULL default '0',
  evt_time datetime NOT NULL default '0000-00-00 00:00:00',
  evt_tg varchar(255) NOT NULL default '',
  evt_referer varchar(255) NOT NULL default '',
  evt_ip varchar(15) NOT NULL default '',
  evt_host varchar(255) NOT NULL default '',
  evt_client varchar(255) NOT NULL default '',
  evt_url varchar(255) NOT NULL default '',
  evt_session_id varchar(32) NOT NULL default '',
  evt_iduser int(11) unsigned NOT NULL default '0',
  evt_info text NOT NULL,
  PRIMARY KEY  (id)
);


# --------------------------------------------------------
#
# Table structure for table `bab_cal_public`
#

CREATE TABLE bab_cal_public (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(60) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  id_dgowner int(11) unsigned NOT NULL default '0',
  idsa int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_dgowner (id_dgowner)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_pub_view_groups'
#

CREATE TABLE bab_cal_pub_view_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_pub_man_groups'
#

CREATE TABLE bab_cal_pub_man_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_pub_grp_groups'
#

CREATE TABLE bab_cal_pub_grp_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_res_view_groups'
#

CREATE TABLE bab_cal_res_view_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_res_man_groups'
#

CREATE TABLE bab_cal_res_man_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_res_grp_groups'
#

CREATE TABLE bab_cal_res_grp_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_cal_res_add_groups'
#

CREATE TABLE bab_cal_res_add_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

# --------------------------------------------------------
#
# Table structure for table `bab_cal_user_options`
#

CREATE TABLE bab_cal_user_options (
  id int(11) unsigned NOT NULL auto_increment,
  id_user int(11) unsigned NOT NULL default '0',
  startday tinyint(4) NOT NULL default '0',
  allday enum('Y','N') NOT NULL default 'Y',
  week_numbers enum('N','Y') NOT NULL default 'N',
  usebgcolor enum('Y','N') NOT NULL default 'Y',
  elapstime tinyint(2) unsigned NOT NULL default '30',
  defaultview tinyint(3) NOT NULL default '0',
  dispdays varchar(20) NOT NULL default '',
  start_time time default NULL,
  end_time time default NULL,
  user_calendarids varchar(255) NOT NULL default '',
  show_update_info enum('N','Y') NOT NULL default 'N',
  iDefaultCalendarAccess SMALLINT( 2 ) DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY id_user (id_user)
);

#
# Table structure for table `bab_stats_addons`
#

CREATE TABLE bab_stats_addons (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_addon char(255) NOT NULL default '',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_addon (st_addon)
);

#
# Table structure for table `bab_stats_articles`
#

CREATE TABLE bab_stats_articles (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_article_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_article_id (st_article_id)
);

#
# Table structure for table `bab_stats_faqqrs`
#

CREATE TABLE `bab_stats_faqqrs` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_faqqr_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_faqqr_id (st_faqqr_id)
);

#
# Table structure for table `bab_stats_faqs`
#

CREATE TABLE `bab_stats_faqs` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_faq_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_faq_id (st_faq_id)
);

#
# Table structure for table `bab_stats_fmfiles`
#

CREATE TABLE `bab_stats_fmfiles` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_fmfile_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_fmfile_id (st_fmfile_id)
);

#
# Table structure for table `bab_stats_fmfolders`
#

CREATE TABLE `bab_stats_fmfolders` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_folder_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_folder_id (st_folder_id)
);

#
# Table structure for table `bab_stats_forums`
#

CREATE TABLE `bab_stats_forums` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_forum_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_forum_id (st_forum_id)
);

#
# Table structure for table `bab_stats_imodules`
#

CREATE TABLE bab_stats_imodules (
  id tinyint(3) unsigned NOT NULL auto_increment,
  module_name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
);

INSERT INTO bab_stats_imodules VALUES (1, 'Others');
INSERT INTO bab_stats_imodules VALUES (2, 'Articles');
INSERT INTO bab_stats_imodules VALUES (3, 'Forums');
INSERT INTO bab_stats_imodules VALUES (4, 'Files');
INSERT INTO bab_stats_imodules VALUES (5, 'Faqs');
INSERT INTO bab_stats_imodules VALUES (6, 'Private home page');
INSERT INTO bab_stats_imodules VALUES (7, 'Public home page');
INSERT INTO bab_stats_imodules VALUES (8, 'Agenda');
INSERT INTO bab_stats_imodules VALUES (9, 'Summary page');
INSERT INTO bab_stats_imodules VALUES (10, 'Directories');
INSERT INTO bab_stats_imodules VALUES (11, 'Search');
INSERT INTO bab_stats_imodules VALUES (12, 'Charts');
INSERT INTO bab_stats_imodules VALUES (13, 'Notes');
INSERT INTO bab_stats_imodules VALUES (14, 'Contacts');
INSERT INTO bab_stats_imodules VALUES (15, 'Administration');
INSERT INTO bab_stats_imodules VALUES (16, 'Vacation');
INSERT INTO bab_stats_imodules VALUES (17, 'Mail');
INSERT INTO bab_stats_imodules VALUES (18, 'Add-ons');
INSERT INTO bab_stats_imodules VALUES (19, 'Login / Registration');
INSERT INTO bab_stats_imodules VALUES (20, 'User options');
INSERT INTO bab_stats_imodules VALUES (21, 'Workflow approbations');
INSERT INTO bab_stats_imodules VALUES (22, 'Ovidentia Editor');
INSERT INTO bab_stats_imodules VALUES (23, 'OvML');
INSERT INTO bab_stats_imodules VALUES (24, 'Task Manager');
INSERT INTO bab_stats_imodules VALUES (25, 'Web services');

#
# Table structure for table `bab_stats_ipages`
#

CREATE TABLE bab_stats_ipages (
  id int(11) unsigned NOT NULL auto_increment,
  page_url text NOT NULL,
  page_name varchar(255) NOT NULL default '',
  id_dgowner int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
);

#
# Table structure for table `bab_stats_modules`
#

CREATE TABLE `bab_stats_modules` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_module_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_module_id (st_module_id)
);

#
# Table structure for table `bab_stats_ovml`
#

CREATE TABLE bab_stats_ovml (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_ovml_file char(255) NOT NULL default '',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_ovml_file (st_ovml_file),
  KEY st_date (st_date),
  KEY st_hour (st_hour)
);

#
# Table structure for table `bab_stats_pages`
#

CREATE TABLE `bab_stats_pages` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_page_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_page_id (st_page_id)
);

#
# Table structure for table `bab_stats_posts`
#

CREATE TABLE `bab_stats_posts` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_post_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_post_id (st_post_id)
);

#
# Table structure for table `bab_stats_search`
#

CREATE TABLE bab_stats_search (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_word char(255) NOT NULL default '',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_word (st_word),
  KEY st_date (st_date),
  KEY st_hour (st_hour)
);

#
# Table structure for table `bab_stats_threads`
#

CREATE TABLE `bab_stats_threads` (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_thread_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour),
  KEY st_thread_id (st_thread_id)
);

#
# Table structure for table `bab_stats_xlinks`
#

CREATE TABLE bab_stats_xlinks (
  st_date date NOT NULL default '0000-00-00',
  st_hour tinyint(3) unsigned NOT NULL default '0',
  st_xlink_url text NOT NULL,
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_date (st_date),
  KEY st_hour (st_hour)
);

#
# Table structure for table `bab_stats_articles_ref`
#

CREATE TABLE bab_stats_articles_ref (
  st_article_id int(11) unsigned NOT NULL default '0',
  st_module_id int(11) unsigned NOT NULL default '0',
  st_hits int(11) unsigned NOT NULL default '0',
  KEY st_article_id (st_article_id),
  KEY st_module_id (st_module_id)
);

CREATE TABLE bab_forumsfiles_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


CREATE TABLE `bab_sites_nonworking_config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_site` int(10) unsigned NOT NULL default '0',
  `nw_type` smallint(5) unsigned NOT NULL default '0',
  `nw_day` varchar(64) NOT NULL default '',
  `nw_text` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `id_site` (`id_site`,`nw_type`)
);

CREATE TABLE `bab_sites_nonworking_days` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_site` int(10) unsigned NOT NULL,
  `nw_day` date NOT NULL default '0000-00-00',
  `nw_type` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `id_site` (`id_site`),
  KEY `nw_day` (`nw_day`)
);



CREATE TABLE bab_users_unavailability (
  id_user int(11) unsigned NOT NULL default '0',
  start_date date NOT NULL default '0000-00-00',
  end_date date NOT NULL default '0000-00-00',
  id_substitute int(11) NOT NULL default '0',
  KEY id_user (id_user,id_substitute)
);

CREATE TABLE bab_cal_events_notes (
  id_event int(10) unsigned NOT NULL default '0',
  id_user int(10) unsigned NOT NULL default '0',
  note text NOT NULL,
  UNIQUE KEY id_event (id_event,id_user)
);

CREATE TABLE bab_cal_events_reminders (
  id_event int(11) unsigned NOT NULL default '0',
  id_user int(11) unsigned NOT NULL default '0',
  day smallint(3) NOT NULL default '0',
  hour smallint(2) NOT NULL default '0',
  minute smallint(2) NOT NULL default '0',
  bemail enum('N','Y') NOT NULL default 'N',
  processed enum('N','Y') NOT NULL default 'N',
  KEY id_event (id_event,id_user)
);


CREATE TABLE `bab_sites_editor` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_site` int(10) unsigned NOT NULL default '0',
  `use_editor` tinyint(3) unsigned NOT NULL default '1',
  `filter_html` tinyint(3) unsigned NOT NULL default '0',
  `tags` text NOT NULL,
  `attributes` text NOT NULL,
  `verify_href` tinyint(3) unsigned NOT NULL default '0',
  `bitstring` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `id_site` (`id_site`)
);



CREATE TABLE `bab_vac_planning` (
  `id_entity` int(10) unsigned NOT NULL default '0',
  `id_user` int(10) unsigned NOT NULL default '0',
  KEY `id_user` (`id_user`)
);

CREATE TABLE bab_stats_preferences (
  id_user int(11) unsigned NOT NULL default '0',
  time_interval smallint(2) unsigned NOT NULL default '0',
  begin_date varchar(10) NOT NULL default '',
  end_date varchar(10) NOT NULL default '',
  separatorchar tinyint(2) NOT NULL default '0',
  UNIQUE KEY id_user (id_user)
);


CREATE TABLE `bab_sites_swish` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`id_site` INT UNSIGNED NOT NULL ,
`swishcmd` VARCHAR( 255 ) NOT NULL ,
`pdftotext` VARCHAR( 255 ) NOT NULL ,
`xls2csv` VARCHAR( 255 ) NOT NULL ,
`catdoc` VARCHAR( 255 ) NOT NULL ,
`unzip` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `id` ) ,
INDEX ( `id_site` )
);

#
# Structure de la table bab_forumsnotify_groups
#

CREATE TABLE bab_forumsnotify_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table bab_dbdirdel_groups
#

CREATE TABLE bab_dbdirdel_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table bab_dbdirexport_groups
#

CREATE TABLE bab_dbdirexport_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table bab_dbdirimport_groups
#

CREATE TABLE bab_dbdirimport_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table bab_dbdirbind_groups
#

CREATE TABLE bab_dbdirbind_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table bab_dbdirunbind_groups
#

CREATE TABLE bab_dbdirunbind_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table bab_dbdirempty_groups
#

CREATE TABLE bab_dbdirempty_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


CREATE TABLE bab_dbdir_fieldsexport (
  id int(11) unsigned NOT NULL auto_increment,
  id_user int(11) unsigned NOT NULL default '0',
  id_directory int(11) unsigned NOT NULL default '0',
  id_field int(11) unsigned NOT NULL default '0',
  ordering int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_user (id_user),
  KEY id_directory (id_directory)
);

CREATE TABLE bab_dbdir_configexport (
  id int(11) unsigned NOT NULL auto_increment,
  id_user int(11) unsigned NOT NULL default '0',
  id_directory int(11) unsigned NOT NULL default '0',
  separatorchar tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_user (id_user),
  KEY id_directory (id_directory)
);


CREATE TABLE `bab_index_files` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `object` varchar(255) NOT NULL default '',
  `index_onload` tinyint(1) unsigned NOT NULL default '0',
  `index_disabled` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `object` (`object`),
  KEY `object_2` (`object`)
);


INSERT INTO `bab_index_files` VALUES (1, 'File manager', 'bab_files', 1, 0);
INSERT INTO `bab_index_files` VALUES (2, 'Articles files', 'bab_art_files', 1, 0);
INSERT INTO `bab_index_files` VALUES (3, 'Forum post files', 'bab_forumsfiles', 1, 0);


CREATE TABLE bab_index_access (
  file_path varchar(255) NOT NULL,
  id_object int(10) unsigned NOT NULL,
  id_object_access int(10) unsigned NOT NULL,
  object varchar(255) NOT NULL,
  PRIMARY KEY  (file_path),
  KEY object (object),
  KEY id_object (id_object)
);


CREATE TABLE `bab_index_spooler` (
`object` VARCHAR( 255 ) NOT NULL ,
`require_once` VARCHAR( 255 ) NOT NULL ,
`function` VARCHAR( 255 ) NOT NULL ,
`function_parameter` LONGTEXT NOT NULL ,
PRIMARY KEY ( `object` )
);

CREATE TABLE `bab_registry` (
  `dirkey` varchar(255) NOT NULL default '',
  `value` text NOT NULL,
  `value_type` varchar(32) NOT NULL default '',
  `create_id_user` int(10) unsigned NOT NULL default '0',
  `update_id_user` int(10) unsigned NOT NULL default '0',
  `createdate` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastupdate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`dirkey`)
);

CREATE TABLE `bab_stats_fmfiles_new` (
  `st_date` date NOT NULL default '0000-00-00',
  `st_hour` tinyint(3) unsigned NOT NULL default '0',
  `st_nb_files` int(11) unsigned NOT NULL default '0',
  `st_id_dgowner` int(11) unsigned NOT NULL default '0',
  KEY `st_date` (`st_date`),
  KEY `st_hour` (`st_hour`),
  KEY `st_nb_files` (`st_nb_files`),
  KEY `st_id_dgowner` (`st_id_dgowner`)
);

CREATE TABLE `bab_stats_articles_new` (
  `st_date` date NOT NULL default '0000-00-00',
  `st_hour` tinyint(3) unsigned NOT NULL default '0',
  `st_nb_articles` int(11) unsigned NOT NULL default '0',
  `st_id_dgowner` int(11) unsigned NOT NULL default '0',
  KEY `st_date` (`st_date`),
  KEY `st_hour` (`st_hour`),
  KEY `st_nb_articles` (`st_nb_articles`),
  KEY `st_id_dgowner` (`st_id_dgowner`)
);


CREATE TABLE bab_tskmgr_categories (
  id int(10) unsigned NOT NULL auto_increment,
  idProjectSpace int(10) unsigned NOT NULL default '0',
  idProject int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  description text NOT NULL,
  color varchar(20) NOT NULL default '',
  refCount int(10) unsigned NOT NULL default '0',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  idUserCreated int(10) unsigned NOT NULL default '0',
  modified datetime NOT NULL default '0000-00-00 00:00:00',
  idUserModified int(10) unsigned NOT NULL default '0',
  bgColor varchar(20) NOT NULL, 
  idUser int(11) unsigned NOT NULL,
  PRIMARY KEY  (id),
  KEY idProjectSpace (idProjectSpace),
  KEY idProject (idProject),
  KEY `name` (`name`),
  KEY refCount (refCount)
) ;


CREATE TABLE bab_tskmgr_default_projects_configuration (
  id int(10) unsigned NOT NULL auto_increment,
  idProjectSpace int(10) unsigned NOT NULL default '0',
  tskUpdateByMgr tinyint(3) unsigned NOT NULL default '1',
  endTaskReminder mediumint(8) unsigned NOT NULL default '5',
  tasksNumerotation tinyint(3) unsigned NOT NULL default '1',
  emailNotice tinyint(3) unsigned NOT NULL default '1',
  faqUrl mediumtext NOT NULL,
  PRIMARY KEY  (id,idProjectSpace),
  KEY idProjectSpace (idProjectSpace)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_default_projects_managers_groups`
# 

CREATE TABLE bab_tskmgr_default_projects_managers_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_default_projects_supervisors_groups`
# 

CREATE TABLE bab_tskmgr_default_projects_supervisors_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_default_projects_visualizers_groups`
# 

CREATE TABLE bab_tskmgr_default_projects_visualizers_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_default_task_responsible_groups`
# 

CREATE TABLE bab_tskmgr_default_task_responsible_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_linked_tasks`
# 

CREATE TABLE bab_tskmgr_linked_tasks (
  id int(10) unsigned NOT NULL auto_increment,
  idTask int(10) unsigned NOT NULL default '0',
  idPredecessorTask int(10) unsigned NOT NULL default '0',
  linkType tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idTask (idTask),
  KEY idPredecessorTask (idPredecessorTask)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_notice`
# 

CREATE TABLE bab_tskmgr_notice (
  id int(10) unsigned NOT NULL auto_increment,
  idProjectSpace int(10) unsigned NOT NULL default '0',
  idProject int(10) unsigned NOT NULL default '0',
  profil int(10) unsigned NOT NULL default '0',
  idEvent int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idProjectSpace (idProjectSpace),
  KEY idProject (idProject),
  KEY profil (profil),
  KEY idEvent (idEvent)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_personnal_task_creator_groups`
# 

CREATE TABLE bab_tskmgr_personnal_task_creator_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_project_creator_groups`
# 

CREATE TABLE bab_tskmgr_project_creator_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_projects`
# 

CREATE TABLE bab_tskmgr_projects (
  id int(10) unsigned NOT NULL auto_increment,
  idProjectSpace int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  description text NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  idUserCreated int(10) unsigned NOT NULL default '0',
  modified datetime NOT NULL default '0000-00-00 00:00:00',
  idUserModified int(10) unsigned NOT NULL default '0',
  isLocked tinyint(3) unsigned NOT NULL default '0',
  state tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idProjectSpace (idProjectSpace),
  KEY isLocked (isLocked),
  KEY state (state)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_projects_comments`
# 

CREATE TABLE bab_tskmgr_projects_comments (
  id int(10) unsigned NOT NULL auto_increment,
  idProject int(10) unsigned NOT NULL default '0',
  commentary text NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  idUserCreated int(10) unsigned NOT NULL default '0',
  modified datetime NOT NULL default '0000-00-00 00:00:00',
  idUserModified int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idProject (idProject)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_projects_configuration`
# 

CREATE TABLE bab_tskmgr_projects_configuration (
  id int(10) unsigned NOT NULL auto_increment,
  idProject int(10) unsigned NOT NULL default '0',
  tskUpdateByMgr tinyint(3) unsigned NOT NULL default '1',
  endTaskReminder mediumint(8) unsigned NOT NULL default '5',
  tasksNumerotation tinyint(3) unsigned NOT NULL default '1',
  emailNotice tinyint(3) unsigned NOT NULL default '1',
  faqUrl mediumtext NOT NULL,
  PRIMARY KEY  (id,idProject),
  KEY idProject (idProject)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_projects_managers_groups`
# 

CREATE TABLE bab_tskmgr_projects_managers_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_projects_revisions`
# 

CREATE TABLE bab_tskmgr_projects_revisions (
  id int(10) unsigned NOT NULL auto_increment,
  idProject int(10) unsigned NOT NULL default '0',
  idProjectComment int(10) unsigned NOT NULL default '0',
  majorVersion int(10) unsigned NOT NULL default '0',
  minorVersion int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idProject (idProject),
  KEY idProjectComment (idProjectComment),
  KEY majorVersion (majorVersion),
  KEY minorVersion (minorVersion)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_projects_spaces`
# 

CREATE TABLE bab_tskmgr_projects_spaces (
  id int(10) unsigned NOT NULL auto_increment,
  idDelegation int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  description text NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  modified datetime NOT NULL default '0000-00-00 00:00:00',
  idUserCreated int(10) unsigned NOT NULL default '0',
  idUserModified int(10) unsigned NOT NULL default '0',
  refCount int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idDelegation (idDelegation)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_projects_supervisors_groups`
# 

CREATE TABLE bab_tskmgr_projects_supervisors_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_projects_visualizers_groups`
# 

CREATE TABLE bab_tskmgr_projects_visualizers_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_specific_fields_area_class`
# 

CREATE TABLE bab_tskmgr_specific_fields_area_class (
  id int(10) unsigned NOT NULL,
  defaultValue text NOT NULL,
  isDefaultValue tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (id)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_specific_fields_base_class`
# 

CREATE TABLE bab_tskmgr_specific_fields_base_class (
  id int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  description text NOT NULL,
  nature tinyint(3) unsigned NOT NULL default '1',
  active tinyint(3) unsigned NOT NULL default '1',
  refCount int(10) unsigned NOT NULL default '0',
  idProjectSpace int(10) unsigned NOT NULL default '0',
  idProject int(10) unsigned NOT NULL default '0',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  idUserCreated int(10) unsigned NOT NULL default '0',
  idUser int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY `name` (`name`),
  KEY idProjectSpace (idProjectSpace),
  KEY idProject (idProject),
  KEY idUser (idUser)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_specific_fields_instance_list`
# 

CREATE TABLE bab_tskmgr_specific_fields_instance_list (
  id int(10) unsigned NOT NULL auto_increment,
  idSpFldClass int(10) unsigned NOT NULL default '0',
  idTask int(10) unsigned NOT NULL default '0',
  `value` text NOT NULL,
  position int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id,idSpFldClass),
  KEY idSpFldClass (idSpFldClass)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_specific_fields_radio_class`
# 

CREATE TABLE bab_tskmgr_specific_fields_radio_class (
  id int(10) unsigned NOT NULL auto_increment,
  idFldBase int(10) unsigned NOT NULL default '0',
  `value` varchar(255) NOT NULL default '',
  isDefaultValue tinyint(3) unsigned NOT NULL default '0',
  position tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_specific_fields_text_class`
# 

CREATE TABLE bab_tskmgr_specific_fields_text_class (
  id int(10) unsigned NOT NULL,
  defaultValue varchar(255) NOT NULL default '',
  isDefaultValue tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (id)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_task_responsible_groups`
# 

CREATE TABLE bab_tskmgr_task_responsible_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_tasks`
# 

CREATE TABLE bab_tskmgr_tasks (
  id int(10) unsigned NOT NULL auto_increment,
  idProject int(10) unsigned NOT NULL default '0',
  taskNumber VARCHAR(9) NOT NULL DEFAULT '0',
  description text NOT NULL,
  shortDescription VARCHAR( 255 ) NOT NULL,
  idCategory int(10) unsigned NOT NULL default '0',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  modified datetime NOT NULL default '0000-00-00 00:00:00',
  idUserCreated int(10) unsigned NOT NULL default '0',
  idUserModified int(10) unsigned NOT NULL default '0',
  class tinyint(3) unsigned NOT NULL default '0',
  participationStatus tinyint(3) unsigned NOT NULL default '0',
  isLinked tinyint(3) unsigned NOT NULL default '0',
  idCalEvent int(10) unsigned NOT NULL default '0',
  hashCalEvent varchar(34) NOT NULL default '0',
  duration double( 10, 2 ) UNSIGNED NOT NULL DEFAULT '0',
  iDurationUnit tinyint( 2 ) UNSIGNED DEFAULT '1' NOT NULL,
  majorVersion int(10) unsigned NOT NULL default '0',
  minorVersion int(10) unsigned NOT NULL default '0',
  color varchar(8) NOT NULL default '',
  position int(10) unsigned NOT NULL default '0',
  completion int(10) unsigned NOT NULL default '0',
  plannedStartDate datetime NOT NULL default '0000-00-00 00:00:00',
  plannedEndDate datetime NOT NULL default '0000-00-00 00:00:00',
  startDate datetime NOT NULL default '0000-00-00 00:00:00',
  endDate datetime NOT NULL default '0000-00-00 00:00:00',
  isNotified tinyint(3) unsigned NOT NULL default '0',
  iPlannedTime double( 10, 2 ) unsigned NOT NULL default '0',			  
  iPlannedTimeDurationUnit tinyint( 2 ) unsigned NOT NULL default '1',			  
  iTime double( 10, 2 ) unsigned NOT NULL default '0',			  
  iTimeDurationUnit tinyint( 2 ) unsigned NOT NULL default '1',			  
  iPlannedCost double( 10, 2 ) unsigned NOT NULL default '0',			  
  iCost double( 10, 2 ) unsigned NOT NULL default '0',	
  iPriority tinyint(2) unsigned NOT NULL default '5',
  PRIMARY KEY  (id,idProject),
  KEY idProject (idProject),
  KEY majorVersion (majorVersion),
  KEY minorVersion (minorVersion)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_tasks_info`
# 

CREATE TABLE bab_tskmgr_tasks_info (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`idTask` INTEGER UNSIGNED NOT NULL default '0',
	`idOwner` INTEGER UNSIGNED NOT NULL default '0',
	`isPersonnal` TINYINT UNSIGNED NOT NULL default '0',
	PRIMARY KEY(`id`),
	INDEX `idTask`(`idTask`),
	INDEX `idOwner`(`idOwner`)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_tasks_comments`
# 

CREATE TABLE bab_tskmgr_personnal_tasks_configuration (
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	idUser INTEGER UNSIGNED NOT NULL default '0',
	endTaskReminder MEDIUMINT UNSIGNED NOT NULL default '5',
	tasksNumerotation TINYINT UNSIGNED NOT NULL default '1',
	emailNotice TINYINT UNSIGNED NOT NULL default '1',
	PRIMARY KEY(`id`),
	INDEX `idUser`(`idUser`)
);

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_tasks_comments`
# 

CREATE TABLE bab_tskmgr_tasks_comments (
  id int(10) unsigned NOT NULL auto_increment,
  idTask int(10) unsigned NOT NULL default '0',
  idProject int(10) unsigned NOT NULL default '0',
  commentary text NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  idUserCreated int(10) unsigned NOT NULL default '0',
  modified datetime NOT NULL default '0000-00-00 00:00:00',
  idUserModified int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idProject (idProject),
  KEY idTask (idTask)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_tasks_responsibles`
# 

CREATE TABLE bab_tskmgr_tasks_responsibles (
  id int(10) unsigned NOT NULL auto_increment,
  idTask int(10) unsigned NOT NULL default '0',
  idResponsible int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idTask (idTask),
  KEY idResponsible (idResponsible)
) ;

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_task_fields`
# 

CREATE TABLE `bab_tskmgr_task_fields` (
  `iId` int(10) UNSIGNED NOT NULL auto_increment,
  `sName` VARCHAR (60) NOT NULL,
  `sLegend` VARCHAR (255) NOT NULL,
  PRIMARY KEY  (`iId`),
  KEY `sName` (`sName`)
);

INSERT INTO `bab_tskmgr_task_fields` (`iId`, `sName`, `sLegend`) VALUES 
(1,	 'sProjectSpaceName', 				'Project space name'),
(2,	 'sProjectName', 					'Project name'),
(3,	 'sTaskNumber', 					'Task number'),
(4,	 'sDescription', 					'Description'),
(5,	 'sShortDescription', 				'Name'),
(6,	 'sClass', 							'Type'),
(7,	 'sCreatedDate', 					'Creation date'),
(8,	 'sModifiedDate', 					'Modified date'),
(9,	 'iIdUserCreated', 					'Modified by'),
(10, 'iIdUserModified', 				'Created by'),
(11, 'iCompletion', 					'Progress rate'),
(12, 'iPriority', 						'Priority'),
(13, 'idOwner', 						'Responsible'),
(14, 'startDate,plannedStartDate', 		'Start Date,Planned'),
(15, 'endDate,plannedEndDate', 			'End Date,Planned'),
(16, 'iTime,iPlannedTime',				'Time,Planned'),
(17, 'iCost,iPlannedCost',				'Cost,Planned'),
(18, 'iDuration',						'Duration'),
(19, 'sCategoryName',					'Category'),
(20, 'sShortDescription,sProjectName',	'Name,Project name');

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_task_selected_fields`
#

CREATE TABLE `bab_tskmgr_task_selected_fields` (
  `iId` int(10) UNSIGNED NOT NULL auto_increment,
  `iIdField` int(10) UNSIGNED NOT NULL,
  `iIdProject` int(10) UNSIGNED NOT NULL,
  `iPosition` SMALLINT( 2 ) NOT NULL,
  `iType` SMALLINT( 2 ) NOT NULL,
  PRIMARY KEY  (`iId`),
  KEY `iIdField` (`iIdField`),
  KEY `iIdProject` (`iIdProject`),
  KEY `iType` (`iType`)
);

INSERT INTO `bab_tskmgr_task_selected_fields` (`iId`, `iIdField`, `iIdProject`, `iPosition`, `iType`) VALUES 
(1, 20, 0, 1, 0),
(2, 6,  0, 2, 0),
(3, 14, 0, 3, 0),
(4, 15, 0, 4, 0);


# --------------------------------------------------------

# 
# Structure de la table `bab_week_days`
# 

CREATE TABLE `bab_week_days` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `weekDay` tinyint(3) unsigned NOT NULL default '0',
  `position` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `weekDay` (`weekDay`),
  KEY `position` (`position`)
) ;


INSERT INTO `bab_week_days` (`id`, `weekDay`, `position`) VALUES 
(1, 0, 6),
(2, 1, 0),
(3, 2, 1),
(4, 3, 2),
(5, 4, 3),
(6, 5, 4),
(7, 6, 5);

# --------------------------------------------------------

# 
# Structure de la table `bab_working_hours`
# 

CREATE TABLE bab_working_hours (
  id int(10) unsigned NOT NULL auto_increment,
  weekDay int(10) unsigned NOT NULL default '0',
  idUser int(10) unsigned NOT NULL default '0',
  startHour time NOT NULL default '00:00:00',
  endHour time NOT NULL default '00:00:00',
  PRIMARY KEY  (id),
  KEY startHour (startHour),
  KEY endHour (endHour)
) ;


INSERT INTO `bab_working_hours` (`id`, `weekDay`, `idUser`, `startHour`, `endHour`) VALUES 
(63, 5, 0, '00:00:00', '24:00:00'),
(62, 4, 0, '00:00:00', '24:00:00'),
(61, 3, 0, '00:00:00', '24:00:00'),
(60, 2, 0, '00:00:00', '24:00:00'),
(59, 1, 0, '00:00:00', '24:00:00');

# --------------------------------------------------------

# 
# Structure de la table `bab_tskmgr_task_list_filter`
# 

CREATE TABLE bab_tskmgr_task_list_filter (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `idUser` INT UNSIGNED NOT NULL,
 `idProject` INT NOT NULL,
 `iTaskClass` INT NOT NULL,
 `iTaskCompletion` INT(11) NOT NULL default '-1',
 PRIMARY KEY(`id`),
 INDEX `idUser`(`idUser`)
);




CREATE TABLE bab_stats_basket_content (
  id int(11) unsigned NOT NULL auto_increment,
  basket_id int(11) unsigned NOT NULL,
  bc_description varchar(255) NOT NULL,
  bc_author int(11) unsigned NOT NULL,
  bc_datetime datetime NOT NULL,
  bc_type tinyint(2) unsigned NOT NULL,
  bc_id int(11) unsigned NOT NULL,
  PRIMARY KEY  (id),
  KEY basket_id (basket_id,bc_type)
);


CREATE TABLE bab_stats_baskets (
  id int(11) unsigned NOT NULL auto_increment,
  basket_name varchar(255) NOT NULL,
  basket_desc varchar(255) NOT NULL,
  basket_author int(11) unsigned NOT NULL,
  basket_datetime datetime NOT NULL,
  id_dgowner int(11) unsigned NOT NULL,
  PRIMARY KEY  (id)
);

CREATE TABLE bab_statsbaskets_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


CREATE TABLE bab_mail_spooler (
  id int(11) unsigned NOT NULL auto_increment,
  mail_hash varchar(255) NOT NULL,
  mail_subject varchar(255) NOT NULL,
  body text NOT NULL,
  altbody text NOT NULL,
  format varchar(32) NOT NULL,
  recipients text NOT NULL,
  mail_data text NOT NULL,
  sent_status tinyint(1) unsigned NOT NULL,
  error_msg varchar(255) NOT NULL,
  mail_date datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY mail_date (mail_date)

);

CREATE TABLE bab_cal_res_upd_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

CREATE TABLE bab_sites_ws_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

CREATE TABLE bab_sites_wsovml_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

CREATE TABLE bab_sites_wsfiles_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

CREATE TABLE bab_stats_connections (
  id_user INT(11) UNSIGNED NOT NULL,
  id_session VARCHAR(32) NOT NULL,
  login_time DATETIME NOT NULL,
  last_action_time DATETIME NOT NULL,
  KEY id_user (id_user),
  KEY id_session (id_session),
  KEY login_time (login_time)	
);


CREATE TABLE `bab_vac_calendar` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_user` int(10) unsigned NOT NULL,
  `monthkey` mediumint(6) unsigned NOT NULL,
  `cal_date` date NOT NULL,
  `ampm` tinyint(1) unsigned NOT NULL,
  `period_type` tinyint(3) unsigned NOT NULL,
  `id_entry` int(10) unsigned NOT NULL,
  `color` varchar(6) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `id_user` (`id_user`,`monthkey`,`cal_date`)
);


CREATE TABLE `bab_vac_rgroup` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `id` )
);


CREATE TABLE `bab_vac_comanager` (
`id_entity` INT UNSIGNED NOT NULL ,
`id_user` INT UNSIGNED NOT NULL ,
PRIMARY KEY ( `id_entity` , `id_user` )
);


# --------------------------------------------------------
#
# Structure de la table 'bab_tags'
#

CREATE TABLE bab_tags (
    id int(11) unsigned NOT NULL auto_increment,
	tag_name VARCHAR (255) not null,
	PRIMARY KEY (id),
    KEY tag_name (tag_name)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_tagsman_groups'
#

CREATE TABLE bab_tagsman_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


CREATE TABLE bab_art_drafts_tags (
  id_draft int(11) unsigned NOT NULL default '0',
  id_tag int(11) unsigned NOT NULL default '0',
  KEY id_draft (id_draft),
  KEY id_tag (id_tag)
);

CREATE TABLE bab_art_tags (
  id_art int(11) unsigned NOT NULL default '0',
  id_tag int(11) unsigned NOT NULL default '0',
  KEY id_art (id_art),
  KEY id_tag (id_tag)
);


CREATE TABLE `bab_event_listeners` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `event_class_name` varchar(100) NOT NULL,
  `function_name` varchar(100) NOT NULL,
  `require_file` varchar(255) NOT NULL,
  `addon_name` varchar(255) NOT NULL,
  `priority` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `event` (`event_class_name`,`function_name`,`require_file`)
);


INSERT INTO `bab_event_listeners` (`id`, `event_class_name`, `function_name`, `require_file`, `addon_name`, `priority`) VALUES 
(1, 'bab_eventBeforePeriodsCreated'		, 'bab_NWD_onCreatePeriods'			, 'utilit/nwdaysincl.php'			, 'core', 0),
(2, 'bab_eventPeriodModified'			, 'bab_vac_onModifyPeriod'			, 'utilit/vacincl.php'				, 'core', 0),
(3, 'bab_eventEditors'					, 'bab_onEventEditors'				, 'utilit/editorincl.php'			, 'core', 0),
(4, 'bab_eventEditorFunctions'			, 'bab_onEditorFunctions'			, 'utilit/editorincl.php'			, 'core', 0),
(5, 'bab_eventEditorContentToEditor'	, 'htmlarea_onContentToEditor'		, 'utilit/htmlareaincl.php'			, 'core', 100),
(6, 'bab_eventEditorRequestToContent'	, 'htmlarea_onRequestToContent'		, 'utilit/htmlareaincl.php'			, 'core', 100),
(7, 'bab_eventEditorContentToHtml'		, 'htmlarea_onContentToHtml'		, 'utilit/htmlareaincl.php'			, 'core', 100),
(8, 'bab_eventLogin'					, 'bab_onEventLogin'				, 'utilit/eventAuthentication.php'	, 'core', 0),
(9, 'bab_eventLogout'					, 'bab_onEventLogout'				, 'utilit/eventAuthentication.php'	, 'core', 0),
(10,'bab_eventBeforeSiteMapCreated'		, 'bab_onBeforeSiteMapCreated'		, 'utilit/sitemap_build.php'		, 'core', 0);



CREATE TABLE `bab_upgrade_messages` (
  `id` int(11) NOT NULL auto_increment,
  `addon_name` varchar(255) NOT NULL,
  `dt_insert` datetime NOT NULL,
  `uid` varchar(255) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `uid` (`uid`)
);

# --------------------------------------------------------
#
# Structure de la table 'bab_dg_acl_groups'
#

CREATE TABLE bab_dg_acl_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_fmnotify_groups'
#

CREATE TABLE bab_fmnotify_groups (
   id int(11) unsigned NOT NULL auto_increment,
   id_object int(11) unsigned DEFAULT '0' NOT NULL,
   id_group int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   KEY id_object (id_object),
   KEY id_group (id_group)
);

CREATE TABLE bab_files_tags (
  id_file int(11) unsigned NOT NULL default '0',
  id_tag int(11) unsigned NOT NULL default '0',
  KEY id_file (id_file),
  KEY id_tag (id_tag)
);


#
# Structure de la table bab_def_topcatcom_groups
#

CREATE TABLE bab_def_topcatcom_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table bab_def_topcatman_groups
#

CREATE TABLE bab_def_topcatman_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table bab_def_topcatmod_groups
#

CREATE TABLE bab_def_topcatmod_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);


#
# Structure de la table bab_def_topcatsub_groups
#

CREATE TABLE bab_def_topcatsub_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);

#
# Structure de la table bab_def_topcatview_groups
#

CREATE TABLE bab_def_topcatview_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);




# --------------------------------------------------------
#
# Sitemap
#

CREATE TABLE bab_sitemap (
   `id` int(11) unsigned NOT NULL auto_increment,
   `id_parent` int(11) unsigned DEFAULT '0' NOT NULL,
   `lf` int(11) unsigned DEFAULT '0' NOT NULL,
   `lr` int(11) unsigned DEFAULT '0' NOT NULL,
   `id_function` varchar(64) NOT NULL,
   `id_dgowner` int(11) unsigned DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `id_function` (`id_function`),
   KEY `lf` (`lf`),
   KEY `lr` (`lr`),
   KEY `id_dgowner` (`id_dgowner`)
);



CREATE TABLE bab_sitemap_function_profile (
   `id_function` varchar(64) NOT NULL,
   `id_profile` int(11) unsigned DEFAULT '0' NOT NULL,
   PRIMARY KEY (`id_function`, `id_profile`)
);


CREATE TABLE bab_sitemap_functions (
   `id_function` varchar(64) NOT NULL,
   `url` varchar(255) NOT NULL,
   `onclick` varchar(255) NOT NULL,
   `folder` tinyint(1) unsigned NOT NULL default '0',
   PRIMARY KEY (`id_function`)
);


CREATE TABLE bab_sitemap_function_labels (
   `id_function` varchar(64) NOT NULL,
   `lang` varchar(32) NOT NULL,
   `name` varchar(255) NOT NULL,
   `description` TEXT NOT NULL,
   PRIMARY KEY (`id_function`,`lang`)
);


CREATE TABLE bab_sitemap_profiles (
   `id` int(11) unsigned NOT NULL auto_increment,
   `uid_functions` int(11) unsigned NOT NULL,
   PRIMARY KEY (`id`)
);


# --------------------------------------------------------
#
# Structure de la table 'bab_dg_categories'
#

CREATE TABLE bab_dg_categories (
	`id` TINYINT (2) UNSIGNED not null AUTO_INCREMENT,
	`name` VARCHAR (60) not null,
	`description` VARCHAR (255) not null,
	`bgcolor` VARCHAR (6) not null,
	PRIMARY KEY (id)
);

#
# Structure de la table bab_dbdirfieldupdate_groups
#

CREATE TABLE bab_dbdirfieldupdate_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
);
