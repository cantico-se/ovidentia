<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE></TITLE>
<META NAME="Generator" CONTENT="Ovidentia">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
<LINK rel="stylesheet" title="Default" href="<?php echo $GLOBALS[babUrl];?>styles/<?php echo $GLOBALS[babStyle]; ?>" type="text/css">
</HEAD>
<BODY>
<br><br><br>
<?php
$m = new babMonthX($month, $year, $callback);
$m->setMaxYear($ymax);
$m->setMinYear($ymin);
$m->printout();
?>
</body>
</html>
