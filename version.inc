; <?php 
;/************************************************************************
; * OVIDENTIA http://www.ovidentia.org                                   *
; ************************************************************************
; * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
; *                                                                      *
; * This file is part of Ovidentia.                                      *
; *                                                                      *
; * Ovidentia is free software; you can redistribute it and/or modify    *
; * it under the terms of the GNU General Public License as published by *
; * the Free Software Foundation; either version 2, or (at your option)  *
; * any later version.                                                   *
; *                                                                      *
; * This program is distributed in the hope that it will be useful, but  *
; * WITHOUT ANY WARRANTY; without even the implied warranty of           *
; * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                 *
; * See the  GNU General Public License for more details.                *
; *                                                                      *
; * You should have received a copy of the GNU General Public License    *
; * along with this program; if not, write to the Free Software          *
; * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
; * USA.                                                                 *
; ************************************************************************


[general]
name="ovidentia"
description="Ovidentia"
db_prefix="bab_"
version="6.7.7"
; forbidden_upgrades="6.3.0,6.3.1"

; available since 5.8.1

ov_version="5.5.3"
php_version="4.1.2"

; available since 5.8.7

mod_mysql="Available"
mysql_version="3.23" 
mod_zlib="Available"


[recommendations]
register_globals="Off"
lang_directory="Available" 
upload_directory="Available"
images_directory="Available" 
mod_xml="Available"
mod_calendar="Available"
mod_ldap="Available"
versions_directory="Available"
mod_imap="Available"
mod_gd2="Available"
mod_mcrypt="Available"
mod_gettext="Available"
; mod_pdf="Available"
; mod_ftp="Available"
; mod_curl="Available"
upload_max_file_size=30M

; available since 6.6.0

mysql_character_set_database="latin1"
mysql_collation_database="latin1_swedish_ci"
mysql_max_allowed_packet=15M
mysql_sql_mode="Undefined"

;*/?>