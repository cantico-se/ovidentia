<?php
class babMonth
{
var $currentMonth;
var $currentYear;

function babMonth($month = "", $year = "")
	{
	if(empty($month))
		$this->currentMonth = Date("n");
	else
		$this->currentMonth = $month;
		
	if(empty($year))
		$this->currentYear = Date("Y");
	else
		$this->currentYear = $year;

	}

function printMonth()
	{
	echo "<tr>";
	echo "<td valign=\"top\" align=\"center\" width=\"100%\">";
	echo "<table border= 1 width=\"150\" cellpadding=\"1\" cellspacing=\"0\" align=\"center\" class=BabSectionBgndSides><tr><td>";
	echo "<table class=BabSectionBgndContent width=\"150\" cellpadding=\"0\" cellspacing=\"0\">";
	echo "<TBODY>";
	echo "<tr>";
	echo "<td class=BabSectionBgndTitle><img src=\"images/box.gif\" width=\"30\" height=\"7\"><b>&nbsp;&nbsp;".$this->title."</td>";
	echo "</tr>";

	$days = date("t", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	for( $i = 1; $i <= $days; $i++)
		{
		echo "<tr>\n";
		for( $j = 1; $j <= 7; $i++, $j++)
			echo "<td>".$i."</td>\n";
		echo "</tr>\n";
		}
	echo "</TBODY>";
	echo "</table>";
	echo "</td></tr></table><br>";
	echo "</td>";
	echo "</tr>";
	}
}


$m = new babMonth(2);

$m->printMonth();
?>