
DROP TABLE IF EXISTS vacations_states;
CREATE TABLE vacations_states (
   id tinyint(2) NOT NULL auto_increment,
   status varchar(255) NOT NULL,
   description text NOT NULL,
   PRIMARY KEY (id)
);

#
# Contenu de la table 'vacations_states'
#

INSERT INTO vacations_states VALUES ( '1', 'Refused', 'Vacation refused');
INSERT INTO vacations_states VALUES ( '2', 'Accepted', 'Vacation accepted');

ALTER TABLE vacations ADD date DATETIME not null AFTER comment;
ALTER TABLE vacations ADD comref TEXT not null AFTER comment;

DROP TABLE IF EXISTS vacationsmana_groups;

CREATE TABLE vacationsman_groups (
	id INT (11) UNSIGNED not null AUTO_INCREMENT,
	id_object INT (11) not null,
	id_group INT (11) not null,
	ordering SMALLINT (4) UNSIGNED not null,
	status TINYINT (2) not null,
	supplier INT (11) UNSIGNED not null,
	PRIMARY KEY (id)
); 