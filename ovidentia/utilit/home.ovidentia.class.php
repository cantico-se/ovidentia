<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/




/**
 * Default home page manager
 * private.html or public.html
 */
class Func_Home_Ovidentia extends Func_Home
{
    public function getDescription()
    {
        return bab_translate('Use public.html and private.html, fallback to articles displayed on homepage if the files are not availables');
    }
    
    
    public function includePage()
    {
        global $file;
        
        if(bab_isUserLogged()) {
            $file = "private.html";
        } else {
            $file = "public.html";
        }

        if( file_exists($GLOBALS['babOvmlPath'].$file)) {
            $incl = "oml";
        } else {
            $incl = "entry";
        }
        
        include $GLOBALS['babInstallPath']."$incl.php";
    }
}