
# phpMyAdmin MySQL-Dump
# version 2.2.4
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
# --------------------------------------------------------

#
# Structure de la table `ad_dbentries`
#

CREATE TABLE ad_dbentries (
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
  PRIMARY KEY  (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `ad_diradd_groups`
#

CREATE TABLE ad_diradd_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  UNIQUE KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `ad_directories`
#

CREATE TABLE ad_directories (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  ldap enum('N','Y') NOT NULL default 'N',
  host tinytext NOT NULL,
  basedn text NOT NULL,
  userdn text NOT NULL,
  password tinyblob NOT NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `ad_directories_fields`
#

CREATE TABLE ad_directories_fields (
  id int(11) unsigned NOT NULL auto_increment,
  id_directory int(11) unsigned NOT NULL default '0',
  id_field int(11) unsigned NOT NULL default '0',
  default_value text NOT NULL,
  modifiable enum('N','Y') NOT NULL default 'N',
  required enum('N','Y') NOT NULL default 'N',
  multilignes enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `ad_dirupdate_groups`
#

CREATE TABLE ad_dirupdate_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  UNIQUE KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `ad_dirview_groups`
#

CREATE TABLE ad_dirview_groups (
  id int(11) unsigned NOT NULL auto_increment,
  id_object int(11) unsigned NOT NULL default '0',
  id_group int(11) unsigned NOT NULL default '0',
  UNIQUE KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Structure de la table `ad_fields`
#

CREATE TABLE ad_fields (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  x_name varchar(255) NOT NULL default '',
  description tinytext NOT NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;

INSERT INTO ad_fields VALUES (1, 'cn', 'cn', 'Common Name');
INSERT INTO ad_fields VALUES (2, 'sn', 'sn', 'Last Name');
INSERT INTO ad_fields VALUES (3, 'mn', '', 'Middle Name');
INSERT INTO ad_fields VALUES (4, 'givenname', 'givenname', 'First Name');
INSERT INTO ad_fields VALUES (5, 'jpegphoto', 'jpegphoto', 'Photo');
INSERT INTO ad_fields VALUES (6, 'email', 'mail', 'E-mail Address');
INSERT INTO ad_fields VALUES (7, 'btel', 'telephonenumber', 'Business Phone');
INSERT INTO ad_fields VALUES (8, 'mobile', 'mobile', 'Mobile Phone');
INSERT INTO ad_fields VALUES (9, 'htel', 'homephone', 'Home Phone');
INSERT INTO ad_fields VALUES (10, 'bfax', 'facsimiletelephonenumber', 'Business Fax');
INSERT INTO ad_fields VALUES (11, 'title', 'title', 'Title');
INSERT INTO ad_fields VALUES (12, 'departmentnumber', 'departmentnumber', 'Department');
INSERT INTO ad_fields VALUES (13, 'organisationname', 'o', 'Company');
INSERT INTO ad_fields VALUES (14, 'bstreetaddress', 'street', 'Business Street');
INSERT INTO ad_fields VALUES (15, 'bcity', 'l', 'Business City');
INSERT INTO ad_fields VALUES (16, 'bpostalcode', 'postalcode', 'Business Postal Code');
INSERT INTO ad_fields VALUES (17, 'bstate', 'st', 'Business State');
INSERT INTO ad_fields VALUES (18, 'bcountry', 'st', 'Business Country');
INSERT INTO ad_fields VALUES (19, 'hstreetaddress', 'homepostaladdress', 'Home Street');
INSERT INTO ad_fields VALUES (20, 'hcity', '', 'Home City');
INSERT INTO ad_fields VALUES (21, 'hpostalcode', '', 'Home Postal Code');
INSERT INTO ad_fields VALUES (22, 'hstate', '', 'Home State');
INSERT INTO ad_fields VALUES (23, 'hcountry', '', 'Home Country');
INSERT INTO ad_fields VALUES (24, 'user1', '', 'User 1');
INSERT INTO ad_fields VALUES (25, 'user2', '', 'User 2');
INSERT INTO ad_fields VALUES (26, 'user3', '', 'User 3');