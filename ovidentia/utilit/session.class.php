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


interface bab_SessionStorage
{
    /**
     * Return session ID or empty string if no session exists
     * @return string
     */
    public function getSessionId();
    public function start();
    

    public function __set($name , $value);
    
    public function __get($name);
    
    public function __isset($name);
    
    public function __unset($name);
}


class bab_SessionMockStorage implements bab_SessionStorage
{

    /**
     * @var string
     */
    private $mockId = '';
    
    private $values;
    
    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->mockId;
    }
    
    public function start()
    {
        $this->mockId = md5(uniqid('', true));
        $this->values = array();
    }
    

    public function __set($name , $value)
    {
        $this->values[$name] = $value;
    }
    
    public function __get($name)
    {
        return $this->values[$name];
    }
    
    public function __isset($name)
    {
        return array_key_exists($name, $this->values);
    }
    
    public function __unset($name)
    {
        unset($this->values[$name]);
    }
    
}


class bab_SessionDefaultStorage implements bab_SessionStorage
{
    /**
     * @return string 
     */
    public function getSessionId()
    {
        return session_id();
    }
    
    public function start()
    {
        global $babUrl;
        session_name(sprintf("OV%u", crc32($babUrl)));
        session_start();
    }
    
    

    public function __set($name , $value)
    {
        $_SESSION[$name] = $value;
    }
    
    public function __get($name)
    {
        return $_SESSION[$name];
    }
    
    public function __isset($name)
    {
        return array_key_exists($name, $_SESSION);
    }
    
    public function __unset($name)
    {
        unset($_SESSION[$name]);
    }
}





class bab_Session
{
    /**
     * @var bab_SessionStorage
     */
    private $storage;
    

    /**
     * 
     */
    public function setStorage(bab_SessionStorage $storage)
    {
        $this->storage = $storage;
    }
    
    /**
     * Get storage instance and start session if not started
     * @return bab_SessionStorage
     */
    protected function getStorage()
    {
        if (!isset($this->storage)) {
            $this->storage = new bab_SessionDefaultStorage();
        }
        
        if (!$this->storage->getSessionId()) {
            $this->storage->start();
        }
        
        return $this->storage;
    }

    /**
     * Start session
     */
	public function start()
	{
	    $this->getStorage(); // getStorage autostart session
	    return; 
	}
	
	
	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->getStorage()->getSessionId();
	}
	
	public function __set($name , $value)
	{
		$this->getStorage()->__set($name, $value);
	}
	
	public function __get($name)
	{
		return $this->getStorage()->__get($name);
	}
	
	public function __isset($name)
	{
		return $this->getStorage()->__isset($name);
	}
	
	public function __unset($name)
	{
		return $this->getStorage()->__unset($name);
	}
}