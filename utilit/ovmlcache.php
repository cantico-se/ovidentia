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
include_once "base.php";


/**
 * 
 *
 *
 */
class bab_ovml_cache
{
	/**
	 * Unique ID based on filename and arguments
	 * @var string
	 */
	private $uid;
	
	
	/**
	 * ovml filename relative to ovml folder
	 * @var string
	 */
	private $file;
	
	/**
	 * ovml arguments
	 * @var array
	 */
	private $args;
	
	
	/**
	 * Cache duration in seconds
	 * @var int
	 */
	private $cache_duration = 3600;
	
	
	/**
	 * 
	 */
	public function __construct($file, $args)
	{
		$this->file = $file;
		
		$this->args = $args;
		
		$uidargs = $args;
		if (isset($uidargs['babCurrentDate']))
		{
			unset($uidargs['babCurrentDate']);
		}
		
		$this->uid = $file . ':' . http_build_query($uidargs);
	}
	
	
	/**
	 * Set cache duration
	 * @param	int	$duration
	 */
	public function setCacheDuration($duration)
	{
		$this->cache_duration = $duration;
	}
	
	
	
	
	
	/**
	 * Cache method based on session (default)
	 * @return string
	 */
	public function session()
	{
		if (!isset($_SESSION['ovml_cache'][$this->uid])) {
			$_SESSION['ovml_cache'][$this->uid] = array();
		}
		$ovmlCache = & $_SESSION['ovml_cache'][$this->uid];
		
		// We check if there the specified ovml is in the cache and the cache is
		// less than 1 hour (or the specified duration) old.
		if (!isset($ovmlCache['timestamp'])
				|| !isset($ovmlCache['content'])
				|| (time() - $ovmlCache['timestamp'] > $this->cache_duration)) {
			$ovmlCache['timestamp'] = time();
			$ovmlCache['content'] = bab_printOvmlTemplate($this->file, $this->args);
		}
		return $ovmlCache['content'];
	}
	
	
	/**
	 * Cache method based on sitemap profile
	 * @return string
	 */
	public function sitemap()
	{
		$profile = bab_sitemap::getProfilVersionUid();
		
		$this->uid .= '-'.$profile;
		
		return $this->file();
	}
	
	/**
	 * Cache method based on a file
	 * @return string
	 */
	public function file()
	{
		require_once dirname(__FILE__).'/path.class.php';
		
		$path = new bab_Path($GLOBALS['babUploadPath'], 'tmp', 'ovmlcache');
		$path->createDir();
		
		$path->push(md5($this->uid));
		
		if (!$path->isFile() || ((time()- filemtime($path->tostring())) > $this->cache_duration))
		{
			$content = bab_printOvmlTemplate($this->file, $this->args);
			file_put_contents($path->tostring(), $content);
			$this->cleanup();
		} else {
			$content = file_get_contents($path->tostring());
		}
		
		return $content;
	}
	
	
	/**
	 * remove cached files older than 10 days
	 */
	private function cleanup()
	{
		// run only once per refresh
		static $done = null;
		
		if (null === $done)
		{
			$path = new bab_Path($GLOBALS['babUploadPath'], 'tmp', 'ovmlcache');
			if ($path->isDir())
			{
				foreach($path as $cachefile)
				{
					if ((time()- @filemtime($path->tostring())) > (10*24*3600))
					{
						try {
							$path->delete();
						} catch (Exception $e)
						{
							bab_debug($e->getMessage());
						}
					}
				}
			}
		
			$done = true;
		}
	}
}