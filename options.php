
<!--#begin changepassword -->

<form method="post" action="{ babPhpSelf }">
<input type="hidden" name="tg" value="options">
<input type="hidden" name="update" value="password">
<table  width="50%" border="0" cellspacing="0" cellpadding="2" align="center">
<tr> 
<td class="BabLoginCadreBackground" align="center" valign="middle"> 
<table  width="100%" border="0" cellspacing="0" cellpadding="5" align="center" class="BabLoginMenuBackground">
<tr> 
<td width="30%" align="right" nowrap valign="middle">{ oldpwd }:</td>
<td width="70%" valign="middle"> 
<input type="password" name="oldpwd" size="20" maxlength="15">
</td>
</tr>
<tr> 
<td align="right" nowrap valign="middle">{ newpwd }:</td>
<td valign="middle"> 
<input type="password" name="newpwd1" size="20" maxlength="15">
</td>
</tr>
<tr> 
<td align="right" nowrap valign="middle">{ renewpwd }:</td>
<td valign="middle"> 
<input type="password" name="newpwd2" size="20" maxlength="15">
</td>
</tr>
<tr> 
<td nowrap>&nbsp;</td>
<td valign="middle"> 
<input type="submit" name="submit" value="{ update }">
<br>
</td>
</tr>
</table>
</td>
</tr>
</table>
</form>
<br>
<!--#end changepassword -->
<!--#begin changeuserinfo -->
<br>
<table border="0" width="90%" cellpadding="3" cellspacing="0" align="center">
<tr>
<td class="BabLoginMenuBackground" align="center" valign="center">{ title }</td>
</tr>
</table>
<br>

<form method="post" action="{ babPhpSelf }">
<input type="hidden" name="tg" value="options">
<input type="hidden" name="update" value="userinfo">
<table  width="50%" border="0" cellspacing="0" cellpadding="2" align="center">
<tr> 
<td class="BabLoginCadreBackground" align="center" valign="middle"> 
<table  width="100%" border="0" cellspacing="0" cellpadding="5" align="center" class="BabLoginMenuBackground">
<tr> 
<td width="30%" align="right" nowrap 
valign="middle">{ password }:</td>
<td width="70%" valign="middle"> 
<input type="password" name="password" size="20" maxlength="15">
</td>
</tr>
<tr> 
<td align="right" nowrap valign="middle">{ firstname }:</td>
<td valign="middle"> 
<input type="text" name="firstname" size="30" maxlength="60" value="{ firstnameval }">
</td>
</tr>
<tr> 
<td align="right" nowrap valign="middle">{ middlename }:</td>
<td valign="middle"> 
<input type="text" name="middlename" size="30" maxlength="60" value="{ middlenameval }">
</td>
</tr>
<tr> 
<td align="right" nowrap valign="middle">{ lastname }:</td>
<td valign="middle"> 
<input type="text" name="lastname" size="30" maxlength="60" value="{ lastnameval }">
</td>
</tr>
<tr> 
<td align="right" nowrap valign="middle">{ nickname }:</td>
<td valign="middle"> 
<input type="text" name="nickname" size="30" maxlength="60" value="{ nicknameval }">
</td>
</tr>
<tr> 
<td align="right" nowrap valign="middle">{ email }:</td>
<td valign="middle"> 
<input type="text" name="email" size="30" maxlength="60" value="{ emailval }">
</td>
</tr>
<tr> 
<td nowrap>&nbsp;</td>
<td valign="middle"> 
<input type="submit" name="submit" value="{ update }">
<br>
</td>
</tr>
</table>
</td>
</tr>
</table>
</form>
<br>
<!--#end changeuserinfo -->

<!--#begin changenickname -->
<!--#if bupdateuserinfo -->
<center><a class="BabSectionsLinkColor" href="javascript: Start('{ urldbmod }', 'OviDir', 'width=550,height=500,status=no,resizable=yes,top=50,left=200,scrollbars=yes');">{ updateuserinfo }</a></center>
<!--#endif bupdateuserinfo -->
<br>
<form method="post" action="{ babPhpSelf }">
<input type="hidden" name="tg" value="options">
<input type="hidden" name="update" value="nickname">
<table  width="50%" border="0" cellspacing="0" cellpadding="2" align="center">
<tr> 
<td class="BabLoginCadreBackground" align="center" valign="middle"> 
<table  width="100%" border="0" cellspacing="0" cellpadding="5" align="center" class="BabLoginMenuBackground">
<tr> 
<td width="30%" align="right" nowrap 
valign="middle">{ password }:</td>
<td width="70%" valign="middle"> 
<input type="password" name="password" size="20" maxlength="15">
</td>
<tr> 
<td align="right" nowrap valign="middle">{ nickname }:</td>
<td valign="middle"> 
<input type="text" name="nickname" size="30" maxlength="60" value="{ nicknameval }">
</td>
</tr>
<tr> 
<td nowrap>&nbsp;</td>
<td valign="middle"> 
<input type="submit" name="submit" value="{ update }">
<br>
</td>
</tr>
</table>
</td>
</tr>
</table>
</form>
<br>
<!--#end changenickname -->
<br><br><br><br>
<!--#begin changelang -->
<br>
<form method="post" action="{ babPhpSelf }">
<input type="hidden" name="tg" value="options">
<input type="hidden" name="update" value="lang">
<table width="50%" border="0" cellspacing="0" cellpadding="2" align="center">
<tr> 
<td class="BabLoginCadreBackground"> 
<table  width="100%" border="0" cellspacing="0" cellpadding="5" align="center" class="BabLoginMenuBackground">
<tr> 
<td align="center" nowrap valign="middle"> 
<table width="90%" border="0" cellspacing="0" cellpadding="2" align="center" class="BabLoginMenuBackground">
<tr> 
<td align="center" valign="middle" width="95%"> 
<table  width="100%" border="0" cellspacing="0" cellpadding="5" align="center" class="BabLoginMenuBackground">
<tr> 
<td colspan="2" align="center">{ title } </td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
<tr> 
<td align="center" nowrap valign="middle"> 
<select name="lang">
<!--#in getnextlang -->
<option value="{ langval }" { langselected }>{ langname }</option>

<!--#endin getnextlang -->
</select>
</td>
</tr>
<tr> 
<td align="center">{ langfiltertxt } </td>
</tr>
<tr>
<td align="center" nowrap valign="middle">
<select name="langfilter">
<!--#in getnextlangfilter -->
<option value="{ langfilterval }" { langfilterselected }>{ langfilterval }</option>
<!--#endin getnextlangfilter -->
</select>
</td>
</tr>
<tr> 
<td align="center"> 
<input type="submit" name="submit" value="{ update }">
<br>
</td>
</tr>
</table>
</td>
</tr>
</table>
<br>
</form>
<!--#end changelang -->
<br><br><br><br>
<!--#begin changeskin -->
<br>
<form name="formskin" method="post" action="{ babPhpSelf }">
<input type="hidden" name="tg" value="options">
<input type="hidden" name="update" value="skin">
<table width="50%" border="0" cellspacing="0" cellpadding="2" align="center">
<tr> 
<td class="BabLoginCadreBackground"> 
<table  width="100%" border="0" cellspacing="0" cellpadding="5" align="center" class="BabLoginMenuBackground">
<tr> 
<td align="center" nowrap valign="middle"> 
<table width="90%" border="0" cellspacing="0" cellpadding="2" align="center" class="BabLoginMenuBackground">
<tr> 
<td align="center" valign="middle" width="95%"> 
<table  width="100%" border="0" cellspacing="0" cellpadding="5" align="center" class="BabLoginMenuBackground">
<tr> 
<td colspan="2" align="center">{ title } </td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
<tr> 
<td align="center" nowrap valign="middle"> 
<select name="skin" onChange="mm(document.forms.formskin.skin.selectedIndex)">
<!--#in getnextskin -->
<option value="{ skinval }" { skinselected }>{ skinname }</option>
<!--#endin getnextskin -->
</select>
<select name="style">
</select>
</td>
</tr>
<tr> 
<td align="center"> 
<input type="submit" name="submit" value="{ update }">
<br>
</td>
</tr>
</table>
</td>
</tr>
</table>
<br>
</form>
<script type="text/javascript">
<!--
var sksel = { skselectedindex };
var stsel = { stselectedindex };
var tab = new Array({ cntskins });
<!--#in getnextskin -->
tab[{ iindex }] = new Array();
<!--#in getnextstyle -->
tab[{ iindex }][tab[{ iindex }].length] = "{ styleval }";
<!--#endin getnextstyle -->
<!--#endin getnextskin -->

function mm(index)
{
	for (i = document.forms.formskin.style.length; i >= 0; i--) {
		document.forms.formskin.style.options[i] = null; 
	}
	for (var i = 0; i < tab[index].length; i++)
	{
	document.forms.formskin.style.options[i] = new Option(tab[index][i],tab[index][i]);
	}
	if( index == sksel )
		document.forms.formskin.style.options[stsel].selected = true;

}
mm(sksel);
//-->
</script>
<!--#end changeskin -->

