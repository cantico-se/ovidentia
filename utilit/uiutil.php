<?php
class fontTag
{
	var $color;
	var $face;
	var $size;

	function fontTag( $color = "", $face= "", $size ="")
		{
		$this->color = $color;
		$this->size = $size;
		$this->face = $face;
		}

	function setDefault()
		{
		$this->color = "";
		$this->size = "";
		$this->face = "";
		}

	function output()
		{
		$result = "";

		if( $this->color != "")
			$result = " color=\"".$this->color."\"";

		if( $this->face != "")
			$result = $result . " face=\"".$this->face."\"";

		if( $this->size != "")
			$result = $result . " size=\"".$this->size."\"";

		return $result;
		}

	function textOut($data)
		{
		$result = "<font". $this->output() . ">";
		$result .= htmlspecialchars($data). "</font>";
		return $result;
		}

	function startTag()
		{
		return "<font". $this->output(1) . ">";
		}

	function endTag()
		{
		return "</font>";
		}

};
?>