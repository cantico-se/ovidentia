<?
function get_js_style_list()
	{
	if ($GLOBALS['babSkin'] == "ovidentia")

		$filename = $GLOBALS['babInstallPath']."skins/".$GLOBALS['babSkin']."/styles/".$GLOBALS['babStyle'];
	else
		$filename = "skins/".$GLOBALS['babSkin']."/styles/".$GLOBALS['babStyle'];
	if (is_file($filename))
		{
		$fcontents = file($filename);

		$get=false;

		while(list( $numero_ligne, $ligne ) = each( $fcontents))
			{
			if (trim($ligne) == "/*BAB_EDITOR_CSS_BEGIN*/") $get=true;
			if (trim($ligne) == "/*BAB_EDITOR_CSS_END*/") $get=false;

			// detecter si il y a un style, couper a l'accolade ou au deux points
			$tmp = explode("{", $ligne);
			$tmp = explode(":", $tmp[0]);
			if (substr($tmp[0],0,1) == "." ) $tmp[0] = substr($tmp[0],1,(strlen($tmp[0])-1));
			$tmp = explode(".", $tmp[0]);
			if (isset($tmp[1])) $tmp[0]=$tmp[1];
			$tmp = trim($tmp[0]);
			if ($tmp != "" && substr($tmp,-2)!="*/" && substr($tmp,0,2)!="/*" && $tmp!="{" && $tmp!="}" && $get)
				{
				//echo $tmp."<br>";
				$affichage = str_replace('_',' ',$tmp);
				$jsligne[] = "\t\"".$affichage."\" : \"".$tmp."\"";
				}
			}
		}


	header("Content-type: application/x-javascript");


	echo "HTMLArea.babstyle = {\n";

	for ($i = 0 ; $i<(count($jsligne)-1) ; $i++ )
		{
		echo $jsligne[$i].",\n";
		}
	// ajouter la derniere ligne
	echo $jsligne[count($jsligne)-1]."\n";

	echo "};\n";
	die();
	}


function get_css_style_list()
	{
	if ($GLOBALS['babSkin'] == "ovidentia")

		$filename = $GLOBALS['babInstallPath']."skins/".$GLOBALS['babSkin']."/styles/".$GLOBALS['babStyle'];
	else
		$filename = "skins/".$GLOBALS['babSkin']."/styles/".$GLOBALS['babStyle'];
	$fcontents = file($filename);

	$get=false;
	$counter = 0;
	header("Content-type: text/css");

	while(list( $numero_ligne, $ligne ) = each( $fcontents))
		{
		if (trim($ligne) == "/*BAB_EDITOR_CSS_BEGIN*/") $get=true;
		if (trim($ligne) == "/*BAB_EDITOR_CSS_END*/") $get=false;

		if ($get)
			{
			if ($counter!=0) echo $ligne;
			$counter++;
			}
		}

	die();
	}



/* main */


switch($idx)
	{
	case "js":
		get_js_style_list();
		break;
	case "css":
		get_css_style_list();
	default:
		break;
	}


?>