<?php
/************************************************************************
 * Ovidentia : add-on : Maintain Language File                          *
 ************************************************************************
 * Copyright (c) 2002, Koblix ( http://www.koblix.com )                 *
 ***********************************************************************/
function ko_mnt_translate($str)
	{
		return bab_translate($str, $GLOBALS['babAddonFolder']);
	}
 	
	class temp
	{
		var $strdesc;
		var $strlang;
		var $langs = array();
		var $langvalue;
		var $langname;
		var $nbrlangs;
		var $strfind;
		var $strupd;
		var $strsubmit;
		var $userlang;
		var $strfindval;
		var $strupdval;
		var $strmsg;
		var $bMessage;
		var $bError;
		
		function getLangCode($fileName)
			{
			$langCode = substr($fileName,5);
			return substr($langCode,0,strlen($langCode)-4);  //$langCode = substr($langCode,0,strlen($langCode)-4)
			}
		
		
		
		function getLangFiles()
			{
				global $babInstallPath;
				//$this->langs[0] = "en";
				
				if (file_exists($babInstallPath."lang"))
					{
						$ko_folder = opendir($babInstallPath."lang");
						$i=0;
						while (false!==($file = readdir($ko_folder))) {
							if ($file != "." && $file != "..") {
							$this->langs[$i] = $this->getLangCode($file);
							$i++;
							}
						}
						closedir($ko_folder);
					}
					
				if (file_exists("lang"))
					{
						$ko_folder = opendir("lang");
						$i=0;
						while (false!==($file = readdir($ko_folder))) {
							if ($file != "." && $file != "..") 
							{
								$newLangCode = $this->getLangCode($file);
								$already_exists = false;
								$a=0;
								while ($a < count($this->langs))
								{
									if ($this->langs[$a] == $newLangCode)
									{
										$already_exists = true;
										
									}
									$a++;
								}//while ($a < count($this->langs))
							
							if (!$already_exists)
								{
									$this->langs[$i] = $newLangCode;
								}
							$i++;
							}//if ($file != "." && $file != "..")
						}//while (false!==($file = readdir($ko_folder)))
						closedir($ko_folder);
					}//if (file_exists("lang"))
				sort($this->langs);
			} // getLangFiles
		
		function temp()
			{
				$this->strdesc = ko_mnt_translate("This function updates a language file of your choice");
				$this->strlang = ko_mnt_translate("Language");
				$this->getLangFiles();		
				$this->nbrlangs = count($this->langs);
				$this->strfind = ko_mnt_translate("Text to translate");
				$this->strupd = ko_mnt_translate("Translation");
				$this->strsubmit = ko_mnt_translate("Translate");
				
                $this->userlang = bab_getUserSetting($GLOBALS['BAB_SESS_USERID'], 'lang');
           
            	if( $this->userlang == "")
	                $this->userlang = $GLOBALS['babLanguage'];
				$this->strfindval = "";
				$this->strupdval = "";
				
				$this->bMessage = false;
				$this->bError = false;
				
			}// function temp
			
					
		function getnext()
			{
				static $i = 0;
				if ( $i < $this->nbrlangs)
					{
						$this->langname = $this->langs[$i];
						$this->langvalue = $this->langs[$i];
						if( $this->userlang == $this->langname )
                    		$this->langselected = "selected";
                		else
                    		$this->langselected = "";
						$i++;
						return true;
					}
				else return false;
			} // function getnext
			
			
		
		
	} // class temp


	
function showLangForm()
{
	global $babBody;
	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."mainlang.html", "mtlupdate"));
} // function showLangForm	

function updateLangFile()
{
	global $babBody;
	global $textfind;
	global $textupdate;
	global $langselect;
	global $babInstallPath;
	
	//$myerrmsg = "";
	
	//function myErrorHandler ($errno, $errstr, $errfile, $errline){
	//global $errmsg;
	//$myerrmsg = "Error number = ".$errno."\n".$errstr."\n"."in file ".$errfile." on line ".$errline;
	//}//myErrorHandler
	
	$temp = new temp();
	$temp->userlang = $langselect;
	$ok = true;
	if ((isset($textfind)&&($textfind !== ""))&&(isset($textupdate)&&($textupdate !== "")))
	{
		$filename = "lang/lang-".$langselect.".xml";
		if (!file_exists($filename))
		{
			if (!file_exists("lang")){
				mkdir ("lang", 0744);
			}
			
			//$olderror = set_error_handler ("myErrorHandler");
			
			if (!copy($babInstallPath."lang/lang-".$langselect.".xml", $filename)) 
			{
				$temp->bError = true;
				$temp->strmsg = ko_mnt_translate("Failed to copie a new")." lang-".$langselect.".xml " .ko_mnt_translate("file!!");//."\n".$GLOBALS["myerrmsg"];
				$ok = false;
			}
			else{
			$ok = true;
			}
			//restore_error_handler();
		}//if !file exists
		
		
	if ($ok){
		$filearray = file($filename);
		$found=false;
		$f=1;
		while ($f<count($filearray)-1){
		
		$start = strpos($filearray[$f], ">")+1;
		$end = strrpos($filearray[$f], "<");
		
		$part1 = substr($filearray[$f],0,$start);
		$part2 = substr($filearray[$f],$start,$end - $start);
		$part3 = substr($filearray[$f],$end);
		
		if ($textfind == $part2){
		$found=true;
			$filearray[$f] = $part1.$textupdate.$part3;
		}
		$f++;
		}
		$fcontents = join("", $filearray);
		$fp = fopen($filename, "w");
		fputs($fp, $fcontents);
		fclose($fp);
		if ($found){
			$temp->bMessage = true;
			$temp->strmsg = ko_mnt_translate("Your text is translated");
		}
		else{
			$temp->bError = true;
			$temp->strmsg = ko_mnt_translate("Your text was not found");
		}
	}//end vd ok
	
	}
	else{
		$temp->bError = true;
		$temp->strmsg = ko_mnt_translate('You have to give a value for both "Text to translate" and "Translation"!');
	}//end if isset
	
	$babBody->babecho(bab_printTemplate($temp, $GLOBALS['babAddonHtmlPath']."mainlang.html", "mtlupdate"));
} // function updateLangFile
 
/* main */
if( !isset($idx ))
	$idx = "first";
$babBody->title = ko_mnt_translate("Maintain Language File");
switch($idx)
	{
	case "first":
		$babBody->addItemMenu("update", "Update", $GLOBALS['babAddonUrl']."mainlang&idx=update");
		showLangForm();
		$idx = "update";
		break;
	case "update":
	default:
		$babBody->addItemMenu("update", "Update", $GLOBALS['babAddonUrl']."mainlang&idx=update");
		updateLangFile();
		break;

	
	
//$babBody->addItemMenu("new", "Add", $GLOBALS['babAddonUrl']."main&idx=new");
		
	}

$babBody->setCurrentItemMenu($idx);

 ?>