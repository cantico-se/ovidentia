<?php

class babMonthX
{
var $currentMonth;
var $currentYear;

function babMonthX($month = "", $year = "")
	{
	global $ymin, $ymax;

	if(empty($month))
		$this->currentMonth = Date("n");
	else
		{
		$this->currentMonth = $month;
		}
		
	if(empty($year))
		{
		$this->currentYear = Date("Y");
		if( !empty($ymin) && !empty($ymax))
			{
			if( $this->currentYear < $ymin && $this->currentYear > $ymax )
				$this->currentYear = $ymin;
			}
		else
			{
			$ymin = Date("Y") - 2;
			$ymax = $ymin + 4;
			}
		}
	else
		{
		$this->currentYear = $year;
		}
	}

function printout()
	{
	global $babMonths, $babDays, $callback, $ymin, $ymax;
	$sec = "<table width=\"150\" cellpadding=\"1\" cellspacing=\"0\" align=\"center\" class=\"BabSectionBgndSides\"><tr><td>";
	$sec .= "<table class=\"BabSectionBgndContent\" width=\"150\" cellpadding=\"0\" cellspacing=\"3\">";
	$sec .= "<TBODY>";
	$sec .= "<tr>";
	$sec .= "<td class=\"BabSectionBgndTitle\" colspan=7><img src=\"images/box.gif\" width=\"30\" height=\"7\" alt=\"\"><b>&nbsp;&nbsp;".$babMonths[date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear))]." ".$this->currentYear."</b></td>";
	$sec .= "</tr>";
	$sec .= "<tr>";
	for( $i= 0; $i < count($babDays); $i++)
		$sec .= "<td bgcolor=\"white\">".substr($babDays[$i], 0, 3)."</td>";
	$sec .= "</tr>";

	$days = date("t", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$daynumber = date("w", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$now = date("j");

	$total = 0;
	for( $i = 1; $i <= 6; $i++)
		{
		$sec .= "<tr>\n";
		
		for( $j = 0; $j < 7; $j++)
			{
			if( $i == 1 &&  $j < $daynumber)
				{
				$sec .= "<td>&nbsp;</td>\n";
				}
			else
				{
				$total++;
				if( $total > $days)
					break;
				if( $total == $now && date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear)) == date("n") && $this->currentYear == date("Y"))
					{
					$sec .= "<td bgcolor=\"white\"><a href='#' onclick=\"self.opener.".$callback."('".$total."','".$this->currentMonth."','".$this->currentYear."');window.close();\">".$total."</a></td>\n";
					}
				else
					$sec .= "<td><a href='#' onclick=\"self.opener.".$callback."('".$total."','".$this->currentMonth."','".$this->currentYear."');window.close();\">".$total."</a></td>\n";

				}
			if( $total > $days)
				break;
			}

		$sec .= "</tr>\n";
		}
	$sec .= "<tr>";
	$sec .= "<td bgcolor=\"white\"><b>";
	if( $this->currentYear > $ymin)
		$sec .= "<a href=\"".$GLOBALS[babUrl]."index.php?tg=month&callback=".$callback."&ymin=".$ymin."&ymax=".$ymax."&month=".$this->currentMonth."&year=".($this->currentYear-1)."\"><<</a>";
	$sec .= "</b></td>";

	$sec .= "<td bgcolor=\"white\"><b>";
	if( $this->currentMonth != 1 || $this->currentYear > $ymin)
		{
		$sec .= "<a href=\"".$GLOBALS[babUrl]."index.php?tg=month&callback=".$callback."&ymin=".$ymin."&ymax=".$ymax."&month=";
		if( $this->currentMonth == 1)
			$sec .= "12&year=".($this->currentYear-1);
		else
			$sec .= ($this->currentMonth - 1)."&year=".$this->currentYear;
		$sec .= "\"><</a>";
		}
	$sec .= "</b></td>";


	$sec .= "<td bgcolor=\"white\" colspan=3><b>&nbsp;</b></td>";
	$sec .= "<td bgcolor=\"white\"><b>";
	if( $this->currentMonth != 12 || $this->currentYear < $ymax)
		{
		$sec .= "<a href=\"".$GLOBALS[babUrl]."index.php?tg=month&callback=".$callback."&ymin=".$ymin."&ymax=".$ymax."&month=";
		if( $this->currentMonth == 12)	
			$sec .= "1&year=".($this->currentYear+1);
		else
			$sec .= ($this->currentMonth+1)."&year=".$this->currentYear;
		$sec .= "\">></a>";
		}

	$sec .= "</b></td>";

	$sec .= "<td bgcolor=\"white\"><b>";
	if( $this->currentYear < $ymax)
		$sec .= "<a href=\"".$GLOBALS[babUrl]."index.php?tg=month&callback=".$callback."&ymin=".$ymin."&ymax=".$ymax."&month=".$this->currentMonth."&year=".($this->currentYear+1)."\">>></a>";
	$sec .= "</b></td>";

	$sec .= "</tr>";
	$sec .= "</TBODY>";
	$sec .= "</table>";
	$sec .= "</td></tr></table><br>";
	return $sec;
	}
}



?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE></TITLE>
<META NAME="Generator" CONTENT="Ovidentia">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
<LINK rel="stylesheet" title="Default" href="<?php echo $GLOBALS[babUrl];?>styles/<?php echo $GLOBALS[babStyle]; ?>" type="text/css">
<script type="text/javascript">
<!--
//-->
</script>
</HEAD>
<BODY>
<br><br><br>
<?php
$month = new babMonthX($month, $year);
echo $month->printout();
?>
</body>
</html>
