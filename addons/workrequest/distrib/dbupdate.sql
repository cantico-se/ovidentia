# phpMyAdmin MySQL-Dump
# version 2.2.4
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Serveur: localhost
# Généré le : Mardi 12 Novembre 2002 à 09:08
# Version du serveur: 3.23.47
# Version de PHP: 4.1.2
# Base de données: `dev`
# --------------------------------------------------------

#
# Structure de la table `wr_taskslist`
#

CREATE TABLE wr_taskslist (
  id int(11) unsigned NOT NULL auto_increment,
  user int(11) unsigned NOT NULL default '0',
  service varchar(255) NOT NULL default '',
  office varchar(255) NOT NULL default '',
  room varchar(255) NOT NULL default '',
  tel varchar(20) NOT NULL default '',
  date_request date NOT NULL default '0000-00-00',
  date_desired date NOT NULL default '0000-00-00',
  deadline varchar(40) NOT NULL default '',
  description text NOT NULL,
  wtype int(11) unsigned NOT NULL default '0',
  worker int(11) unsigned NOT NULL default '0',
  worker_tel varchar(20) NOT NULL default '',
  date_start date NOT NULL default '0000-00-00',
  date_end date NOT NULL default '0000-00-00',
  status smallint(6) unsigned NOT NULL default '0',
  information text NOT NULL,
  date_update date NOT NULL default '0000-00-00',
  user_update int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY date_request (date_request),
  KEY user (user)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `wr_worksagents_groups`
#

CREATE TABLE wr_worksagents_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `wr_workslist`
#

CREATE TABLE wr_workslist (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) default NULL,
  description varchar(255) default NULL,
  wtype smallint(6) unsigned NOT NULL default '0',
  manager int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY wtype (wtype),
  KEY manager (manager)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `wr_worksothers_groups`
#

CREATE TABLE wr_worksothers_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `wr_workstypes`
#

CREATE TABLE wr_workstypes (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `wr_worksusers_groups`
#

CREATE TABLE wr_worksusers_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_object (id_object),
  KEY id_group (id_group)
) TYPE=MyISAM;
