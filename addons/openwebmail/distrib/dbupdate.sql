
# phpMyAdmin MySQL-Dump
# version 2.2.4
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
# --------------------------------------------------------

#
# Structure de la table `ow_configuration`
#

CREATE TABLE ow_configuration (
  foption char(255) NOT NULL default '',
  fvalue char(255) NOT NULL default '',
  UNIQUE KEY foption (foption)
);

#
# Structure de la table `ow_users`
#

CREATE TABLE ow_users (
  id_user int(11) unsigned NOT NULL default '0',
  loginname varchar(255) NOT NULL default '',
  password blob NOT NULL,
  popup enum('Y','N') NOT NULL default 'Y',
  UNIQUE KEY id_user (id_user)
);



