	function test()
	{
		document.write('Chargement correcte');
	}

	function UtilBeginScript()
    {
	return String.fromCharCode(60, 115, 99, 114, 105, 112, 116, 62);
    }

    function UtilEndScript()
    {
	return String.fromCharCode(60, 47, 115, 99, 114, 105, 112, 116, 62);
    }

	
	function IDGenerator(nextID)
	{
		this.nextID = nextID;
		this.GenerateID = IDGeneratorGenerateID;
	}

	function IDGeneratorGenerateID()
	{
		return this.nextID++;
	}

	var BUTTON_IMAGE_PATH = "images";
	var BUTTON_IMAGE_PREFIX = "images";
	var BUTTON_DIV_PREFIX = "buttonDiv";
	var BUTTON_PAD1_PREFIX = "buttonPad1";
	var BUTTON_PAD2_PREFIX = "buttonPad2";
	var buttonMap = new Object();

	function Button
	(
		idGenerator,
		caption,
		action,
		image
	)
	{
		this.idGenerator = idGenerator;
		this.caption = caption;
		this.action = action;
		this.image = image;
		this.enabled = true;
		this.Instantiate = ButtonInstantiate;
		this.Enable = ButtonEnable;
	}

	function ButtonInstantiate()
	{
		this.id = this.idGenerator.GenerateID();
		buttonMap[this.id] = this;
		var html = "";
		html += '<div id="';
		html += BUTTON_DIV_PREFIX;
		html += this.id;
		html += '" class="ButtonNormal"';
		html += ' onselectstart="ButtonOnSelectStart()"';
		html += ' ondragstart="ButtonOnDragStart()"';
		html += ' onmousedown="ButtonOnMouseDown(this)"';
		html += ' onmouseup="ButtonOnMouseUp(this)"';
		html += ' onmouseout="ButtonOnMouseOut(this)"';
		html += ' onmouseover="ButtonOnMouseOver(this)"';
		html += ' onclick="ButtonOnClick(this)"';
		html += ' ondblclick="ButtonOnDblClick(this)"';
		html += '>';
		html += '<table cellpadding=0 cellspacing=0 border=0><tr><td><img id="';
		html += BUTTON_PAD1_PREFIX;
		html += this.id;
		html += '" width=2 height=2></td><td></td><td></td></tr><tr><td></td><td>';
		html += '<img id="';
		html += BUTTON_IMAGE_PREFIX;
		html += this.id;
		html += '" src="';
		html += this.image;
		html += '" title="';
		html += this.caption;
		html += '" class="Image"';
		html += '>';
		html += '</td><td></td></tr><tr><td></td><td></td><td><img id="';
		html += BUTTON_PAD2_PREFIX;
		html += this.id;
		html += '" width=2 height=2></td></tr></table>';
		html += '</div>';
		document.write(html);
	}

	function ButtonEnable(enabled)
	{
		this.enabled = enabled;
		if (this.enabled)
		{
			document.all[BUTTON_DIV_PREFIX + this.id].className = "ButtonNormal";
		}
		else
		{
			document.all[BUTTON_DIV_PREFIX + this.id].className = "ButtonDisabled";
		}
	}

	function ButtonOnSelectStart()
	{
		window.event.returnValue = false;
	}

	function ButtonOnDragStart()
	{
		window.event.returnValue = false;
	}

	function ButtonOnMouseDown(element)
	{
		if (event.button == 1)
		{
			var id = element.id.substring(BUTTON_DIV_PREFIX.length, element.id.length);
			var button = buttonMap[id];
			if (button.enabled)
			{
				ButtonPushButton(id);
			}
		}
	}

	function ButtonOnMouseUp(element)
	{
		if (event.button == 1)
		{
			var id = element.id.substring(BUTTON_DIV_PREFIX.length, element.id.length);
			var button = buttonMap[id];
			if (button.enabled)
			{
				ButtonReleaseButton(id);
			}
		}
	}

	function ButtonOnMouseOut(element)
	{
		var id = element.id.substring(BUTTON_DIV_PREFIX.length, element.id.length);
		var button = buttonMap[id];
		if (button.enabled)
		{
			ButtonReleaseButton(id);
		}
	}

	function ButtonOnMouseOver(element)
	{
		var id = element.id.substring(BUTTON_DIV_PREFIX.length, element.id.length);
		var button = buttonMap[id];
		if (button.enabled)
		{
			ButtonReleaseButton(id);
			document.all[BUTTON_DIV_PREFIX + id].className = "ButtonMouseOver";
		}
	}

	function ButtonOnClick(element)
	{
		var id = element.id.substring(BUTTON_DIV_PREFIX.length, element.id.length);
		var button = buttonMap[id];
		if (button.enabled)
		{
			eval(button.action);
		}
	}

	function ButtonOnDblClick(element)
	{
		ButtonOnClick(element);
	}

	function ButtonPushButton(id)
	{
		document.all[BUTTON_PAD1_PREFIX + id].width = 3;
		document.all[BUTTON_PAD1_PREFIX + id].height = 3;
		document.all[BUTTON_PAD2_PREFIX + id].width = 1;
		document.all[BUTTON_PAD2_PREFIX + id].height = 1;
		document.all[BUTTON_DIV_PREFIX + id].className = "ButtonPressed";
	}

	function ButtonReleaseButton(id)
	{
		document.all[BUTTON_PAD1_PREFIX + id].width = 2;
		document.all[BUTTON_PAD1_PREFIX + id].height = 2;
		document.all[BUTTON_PAD2_PREFIX + id].width = 2;
		document.all[BUTTON_PAD2_PREFIX + id].height = 2;
		document.all[BUTTON_DIV_PREFIX + id].className = "ButtonNormal";
	}
//--------------------------------------------------------------------------
	var COLOR_CHOOSER_DIV_PREFIX = "colorChooserDiv";
    var COLOR_CHOOSER_IMG_PREFIX = "colorChooserImg";
    var COLOR_CHOOSER_ICON_PREFIX = "colorChooserIcon";
    var colorChooserMap = new Object();

    function ColorChooser
    (
	    idGenerator,
	    numRows,
	    numCols,
	    color,
	    callback
    )
    {
	    this.idGenerator = idGenerator;
	    this.numRows = numRows;
	    this.numCols = numCols;
	    this.color = color;
	    this.callback = callback;
	    this.Instantiate = ColorChooserInstantiate;
	    this.Show = ColorChooserShow;
	    this.Hide = ColorChooserHide;
	    this.IsShowing = ColorChooserIsShowing;
	    this.SetUserData = ColorChooserSetUserData;
    }

    function ColorChooserInstantiate(Editor_id)
    {
	    this.id = this.idGenerator.GenerateID();
	    colorChooserMap[this.id] = this;
	    var html = '';
		html += '<style type=text/css> TABLE.CTab { CURSOR: hand } </STYLE>';
	    html += '<table>';
	    html += '<tr>';
	    html += '<td>';
	    html += '<div id="' + COLOR_CHOOSER_DIV_PREFIX + this.id + '" style="display:none;position:absolute;background-color:buttonface;border-left:buttonhighlight solid 1px;border-top:buttonhighlight solid 1px;border-right:buttonshadow solid 1px;border-bottom:buttonshadow solid 1px">';
	    html += '<table class=CTab onclick="ColorChooserOnClick(' + this.id + ',' + Editor_id + ')" cellSpacing=0 cellPadding=0 border=2>';
		html += '<TBODY>';
	    for (var i = 0; i < this.numRows; i++) {
		    html += '<tr>';
		    for (var j = 0; j < this.numCols; j++) {
				var k = i * this.numCols + j;
			    html += '<div id="' + COLOR_CHOOSER_ICON_PREFIX + this.id + '_' + k + 'style="border:buttonface solid 1px">';
				html += this.color[k];
			    html += '</div>';
		    }
			html += '<tr>';
	    }
		//html += '</tr>';
		html += '</TBODY>';
	    html += '</table>';
	    html += '</div>';
	    html += '</td>';
	    html += '</tr>';
	    html += '</table>';
	    document.write(html);
    }

    function ColorChooserShow(x, y)
    {
	    eval(COLOR_CHOOSER_DIV_PREFIX + this.id).style.left = x;
	    eval(COLOR_CHOOSER_DIV_PREFIX + this.id).style.top = y;
	    eval(COLOR_CHOOSER_DIV_PREFIX + this.id).style.display = "block";
    }

    function ColorChooserHide()
    {
	    eval(COLOR_CHOOSER_DIV_PREFIX + this.id).style.display = "none";
    }

    function ColorChooserIsShowing()
    {
	    return eval(COLOR_CHOOSER_DIV_PREFIX + this.id).style.display == "block";
    }

    function ColorChooserSetUserData(userData)
    {
	this.userData = userData;
    }

    function ColorChooserOnClick(id, Editor_id)
    {
		var Color = event.srcElement.title;
		var colorChooser = colorChooserMap[id];

		colorChooser.Hide();
		colorChooser.callback(Color, Editor_id);
    }
	
	var EDITOR_COMPOSITION_PREFIX = "editorComposition";
	var EDITOR_PARAGRAPH_PREFIX = "editorParagraph";
	var EDITOR_LIST_AND_INDENT_PREFIX = "editorListAndIndent";
	var EDITOR_TOP_TOOLBAR_PREFIX = "editorTopToolbar";
	var EDITOR_BOTTOM_TOOLBAR_PREFIX = "editorBottomToolbar";
//-------------------------------------------------------------
	var EDITOR_FOREGROUND_BUTTON_PREFIX = "editorForegroundButton";
	var EDITOR_COLOR_CHOOSER_PREFIX = "editorColorChooser";
	var EDITOR_BACK_COLOR_CHOOSER_PREFIX = "editorBackColorChooser";
	var EDITOR_FORE_COLOR_CHOOSER_PREFIX = "editorForeColorChooser";
	var EDITOR_BACKGROUND_BUTTON_PREFIX = "editorBackgroundButton";
//-------------------------------------------------------------
	var html = "";
	var NumZone = 0;
	var Zone = new Object();
	var ZForm = new Object();
//-----------------------------------------------------------------
//				Initialisation de l'editeur
//-----------------------------------------------------------------

	var editorMap = new Object();
	var editorIDGenerator = null;

	function Editor(idGenerator, path)
	{
		BUTTON_IMAGE_PATH = path;
		this.idGenerator = idGenerator;
		this.textMode = new Object();
		this.brief = false;
		this.instantiated = false;
		//this.Instantiate = EditorInstantiate;
		this.GetText = EditorGetText;
		this.SetText = EditorSetText;
		this.GetHTML = EditorGetHTML;
		this.SetHTML = EditorSetHTML;
		this.GetBrief = EditorGetBrief;
		this.SetBrief = EditorSetBrief;
		this.ToolBar = EditorToolBarInstantiate;
		this.InFrame = EditorFrameInstantiate;
		this.ColorPanel = EditorColorPanelInstantiate;
		this.FinalInstantiate = EditorFinalInstantiate;
		this.CloseInitSection = EditorCloseInitSection;
	}

	function EditorToolBarInstantiate()
	{
		if (this.instantiated) {
			return;
		}
		this.id = this.idGenerator.GenerateID();
		editorMap[this.id] = this;
		editorIDGenerator = this.idGenerator;

		html = "";
		html += "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">";
			html += "<tr>";
				html += "<td id=\"" + EDITOR_TOP_TOOLBAR_PREFIX + this.id + "\" class=\"Toolbar\">";
					html += "<table cellpaddin=\"0\" cellspacing=\"0\" border=\"0\">";
						html += "<tr>";
							html += "<td>";
								html += "<div class=\"Space\"></div>";
							html += "</td>";
							html += "<td>";
								html += "<div class=\"Swatch\"></div>";
							html += "</td>";
							html += "<td>";
								html += "<select name=\"police\" class=\"List\" onchange=\"EditorOnFont(" + this.id + ", this)\">";
									html += "<option class=\"Heading\">"+STR_FONT+"</option>";
									html += "<option value=\"Arial\">Arial</option>";
									html += "<option value=\"Arial Black\">Arial Black</option>";
									html += "<option value=\"Arial Narrow\">Arial Narrow</option>";
									html += "<option value=\"Comic Sans MS\">Comic Sans MS</option>";
									html += "<option value=\"Courier New\">Courier New</option>";
									html += "<option value=\"System\">System</option>";
									html += "<option value=\"Times New Roman\">Times New Roman</option>";
									html += "<option value=\"Verdana\">Verdana</option>";
									html += "<option value=\"Wingdings\">Wingdings</option>";
						        html += "</select>";
							html += "</td>";
							html += "<td>";
								html += "<select class=\"List\" onchange=\"EditorOnSize(" + this.id + ", this)\">";
									html += "<option class=\"Heading\">"+STR_SIZE+"</option>";
									html += "<option value=\"1\">1</option>";
									html += "<option value=\"2\">2</option>";
									html += "<option value=\"3\">3</option>";
									html += "<option value=\"4\">4</option>";
									html += "<option value=\"5\">5</option>";
									html += "<option value=\"6\">6</option>";
									html += "<option value=\"7\">7</option>";
								html += "</select>";
							html += "</td>";
							html += "<td>";
								html += "<div class=\"Divider\"></div>";
							html += "</td>";
							html += "<td class=\"Text\">";
								html += "<input name=\"editorCheckbox\" type=\"checkbox\" onclick=\"EditorOnViewHTMLSource(" + this.id + ", this.checked)\">";
								html += STR_VIEW_HTML;
							html += "</td>";
						html += "</tr>";
					html += "</table>";
				html += "</td>";
			html += "</tr>";
			html += "<tr>";
				html += "<td id=\"" + EDITOR_BOTTOM_TOOLBAR_PREFIX + this.id + "\" class=\"Toolbar\">";
					html += "<table cellpaddin=\"0\" cellspacing=\"0\" border=\"0\">";
						html += "<tr>";
							html += "<td>";
								html += "<div class=\"Space\"></div>";
							html += "</td>";
							html += "<td>";
								html += "<div class=\"Swatch\"></div>";
							html += "</td>";
		//Couper
							html += "<td>";
								html += UtilBeginScript();
								html += "var cutButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_CUT+"\",";
								html += "\"EditorOnCut(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/cut.gif\"";
								html += ");";
								html += "cutButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
		//Copier
							html += "<td>";
								html += UtilBeginScript();
								html += "var copyButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_COPY+"\",";
								html += "\"EditorOnCopy(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/copy.gif\"";
								html += ");";
								html += "copyButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
		//Coller
							html += "<td>";
								html += UtilBeginScript();
								html += "var pasteButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_PASTE+"\",";
								html += "\"EditorOnPaste(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/paste.gif\"";
								html += ");";
								html += "pasteButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
		
		//Separation
							html += "<td>";
								html += "<div class=\"Divider\"></div>";
							html += "</td>";

		//Gras
							html += "<td>";
								html += UtilBeginScript();
								html += "var boldButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_BOLD+"\",";
								html += "\"EditorOnBold(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/bold.gif\"";
								html += ");";
								html += "boldButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
							html += "<td>";
								html += UtilBeginScript();
								html += "var italicButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_ITALIC+"\",";
								html += "\"EditorOnItalic(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/italic.gif\"";
								html += ");";
								html += "italicButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
							html += "<td>";
								html += UtilBeginScript();
								html += "var underlineButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_UNDERLINED+"\",";
								html += "\"EditorOnUnderline(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/uline.gif\"";
								html += ");";
								html += "underlineButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
							html += "<td>";
								html += "<div class=\"Divider\"></div>";
							html += "</td>";

							html += "<td id=\"" + EDITOR_FOREGROUND_BUTTON_PREFIX + this.id + "\">";
								html += UtilBeginScript();
								html += "var foregroundColorButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_FONT_COLOR+"\",";
								html += "\"EditorOnStartChangeForegroundColor(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/tpaint.gif\"";
								html += ");";
								html += "foregroundColorButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";

							html += "<td id=\"" + EDITOR_BACKGROUND_BUTTON_PREFIX + this.id + "\">";
								html += UtilBeginScript();
								html += "var backgroundColorButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_BACKGROUNG_COLOR+"\",";
								html += "\"EditorOnStartChangeBackgroundColor(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/parea.gif\"";
								html += ");";
								html += "backgroundColorButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";

							html += "<td>";
								html += "<div class=\"Divider\"></div>";
							html += "</td>";
							html += "<td>";
								html += UtilBeginScript();
								html += "var alignLeftButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_LEFT_ALIGNED+"\",";
								html += "\"EditorOnAlignLeft(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/aleft.gif\"";
								html += ");";
								html += "alignLeftButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
							html += "<td>";
								html += UtilBeginScript();
								html += "var centerButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_CENTER+"\",";
								html += "\"EditorOnCenter(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/center.gif\"";
								html += ");";
								html += "centerButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
							html += "<td>";
								html += UtilBeginScript();
								html += "var alignRightButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_RIGHT_ALIGNED+"\",";
								html += "\"EditorOnAlignRight(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/aright.gif\"";
								html += ");";
								html += "alignRightButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
							html += "<td>";
								html += "<div class=\"Divider\"></div>";
							html += "</td>";
							html += "<td id=\"" + EDITOR_LIST_AND_INDENT_PREFIX + this.id + "\" style=\"display:" + (this.brief ? "none" : "inline") + "\">";
								html += "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
									html += "<tr>";
										html += "<td>";
											html += UtilBeginScript();
											html += "var numberedListButton = new Button(";
											html += "editorIDGenerator,";
											html += "\""+STR_NUMBERED_LIST+"\",";
											html += "\"EditorOnNumberedList(" + this.id + ")\",";
											html += "\""+BUTTON_IMAGE_PATH+"/nlist.gif\"";
											html += ");";
											html += "numberedListButton.Instantiate();";
											html += UtilEndScript();
										html += "</td>";
										html += "<td>";
											html += UtilBeginScript();
											html += "var bullettedListButton = new Button(";
											html += "editorIDGenerator,";
											html += "\""+STR_BULLET_LIST+"\",";
											html += "\"EditorOnBullettedList(" + this.id + ")\",";
											html += "\""+BUTTON_IMAGE_PATH+"/blist.gif\"";
											html += ");";
											html += "bullettedListButton.Instantiate();";
											html += UtilEndScript();
										html += "</td>";
										html += "<td>";
											html += "<div class=\"Divider\"></div>";
										html += "</td>";
										html += "<td>";
											html += UtilBeginScript();
											html += "var decreaseIndentButton = new Button(";
											html += "editorIDGenerator,";
											html += "\""+STR_DECREASE_INDENT+"\",";
											html += "\"EditorOnDecreaseIndent(" + this.id + ")\",";
											html += "\""+BUTTON_IMAGE_PATH+"/ileft.gif\"";
											html += ");";
											html += "decreaseIndentButton.Instantiate();";
											html += UtilEndScript();
										html += "</td>";
										html += "<td>";
											html += UtilBeginScript();
											html += "var increaseIndentButton = new Button(";
											html += "editorIDGenerator,";
											html += "\""+STR_INCREASE_INDENT+"\",";
											html += "\"EditorOnIncreaseIndent(" + this.id + ")\",";
											html += "\""+BUTTON_IMAGE_PATH+"/iright.gif\"";
											html += ");";
											html += "increaseIndentButton.Instantiate();";
											html += UtilEndScript();
										html += "</td>";
										html += "<td>";
											html += "<div class=\"Divider\"></div>";
										html += "</td>";
									html += "</tr>";
								html += "</table>";
							html += "</td>";
							html += "<td>";
								html += UtilBeginScript();
								html += "var createHyperlinkButton = new Button(";
								html += "editorIDGenerator,";
								html += "\""+STR_INSERT_LINK+"\",";
								html += "\"EditorOnCreateHyperlink(" + this.id + ")\",";
								html += "\""+BUTTON_IMAGE_PATH+"/wlink.gif\"";
								html += ");";
								html += "createHyperlinkButton.Instantiate();";
								html += UtilEndScript();
							html += "</td>";
						html += "</tr>";
					html += "</table>";
				html += "</td>";
			html += "</tr>";
		html += "</table>";

		html += UtilBeginScript();
		html += "var " + EDITOR_FORE_COLOR_CHOOSER_PREFIX + this.id + " = new ColorChooser(";
		html += "editorIDGenerator,";
		html += "5, 8,";
		html += "[";
		html += "\"<TD title=#000000 bgColor=#000000>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#993300 bgColor=#993300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333300 bgColor=#333300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#003300 bgColor=#003300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#003366 bgColor=#003366>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#000080 bgColor=#000080>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333399 bgColor=#333399>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333333 bgColor=#333333>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#800000 bgColor=#800000>&nbsp;</TD>\",";
		html += "\"<TD title=#FF6000 bgColor=#FF6000>&nbsp;</TD>\",";
		html += "\"<TD title=#808000 bgColor=#808000>&nbsp;</TD>\",";
		html += "\"<TD title=#008000 bgColor=#008000>&nbsp;</TD>\",";
		html += "\"<TD title=#008080 bgColor=#008080>&nbsp;</TD>\",";
		html += "\"<TD title=#0000FF bgColor=#0000FF>&nbsp;</TD>\",";
		html += "\"<TD title=#666699 bgColor=#666699>&nbsp;</TD>\",";
		html += "\"<TD title=#808080 bgColor=#808080>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF0000 bgColor=#FF0000>&nbsp;</TD>\",";
		html += "\"<TD title=#FF9900 bgColor=#FF9900>&nbsp;</TD>\",";
		html += "\"<TD title=#99CC00 bgColor=#99CC00>&nbsp;</TD>\",";
		html += "\"<TD title=#339966 bgColor=#339966>&nbsp;</TD>\",";
		html += "\"<TD title=#33CCCC bgColor=#33CCCC>&nbsp;</TD>\",";
		html += "\"<TD title=#3366FF bgColor=#3366FF>&nbsp;</TD>\",";
		html += "\"<TD title=#800080 bgColor=#800080>&nbsp;</TD>\",";
		html += "\"<TD title=#969696 bgColor=#969696>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF00FF bgColor=#FF00FF>&nbsp;</TD>\",";
		html += "\"<TD title=#FFCC00 bgColor=#FFCC00>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFF00 bgColor=#FFFF00>&nbsp;</TD>\",";
		html += "\"<TD title=#00FF00 bgColor=#00FF00>&nbsp;</TD>\",";
		html += "\"<TD title=#00FFFF bgColor=#00FFFF>&nbsp;</TD>\",";
		html += "\"<TD title=#00CCFF bgColor=#00CCFF>&nbsp;</TD>\",";
		html += "\"<TD title=#993366 bgColor=#993366>&nbsp;</TD>\",";
		html += "\"<TD title=#C0C0C0 bgColor=#C0C0C0>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF99CC bgColor=#FF99CC>&nbsp;</TD>\",";
		html += "\"<TD title=#FFCC99 bgColor=#FFCC99>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFF99 bgColor=#FFFF99>&nbsp;</TD>\",";
		html += "\"<TD title=#CCFFCC bgColor=#CCFFCC>&nbsp;</TD>\",";
		html += "\"<TD title=#CCFFFF bgColor=#CCFFFF>&nbsp;</TD>\",";
		html += "\"<TD title=#99CCFF bgColor=#99CCFF>&nbsp;</TD>\",";
		html += "\"<TD title=#CC99FF bgColor=#CC99FF>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFFFF bgColor=#FFFFFF>&nbsp;&nbsp;&nbsp;</TD>\"";
		html += "],";
		html += "EditorOnEndChangeForegroundColor";
		html += ");";
		html += EDITOR_FORE_COLOR_CHOOSER_PREFIX + this.id + ".SetUserData(" + this.id + ");";
		html += EDITOR_FORE_COLOR_CHOOSER_PREFIX + this.id + ".Instantiate(" + this.id + ");";
		html += UtilEndScript();

		//zone de choix de la couleur de fond
		html += UtilBeginScript();
		html += "var " + EDITOR_BACK_COLOR_CHOOSER_PREFIX + this.id + " = new ColorChooser(";
		html += "editorIDGenerator,";
		html += "5, 8,";
		html += "[";
		html += "\"<TD title=#000000 bgColor=#000000>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#993300 bgColor=#993300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333300 bgColor=#333300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#003300 bgColor=#003300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#003366 bgColor=#003366>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#000080 bgColor=#000080>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333399 bgColor=#333399>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333333 bgColor=#333333>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#800000 bgColor=#800000>&nbsp;</TD>\",";
		html += "\"<TD title=#FF6000 bgColor=#FF6000>&nbsp;</TD>\",";
		html += "\"<TD title=#808000 bgColor=#808000>&nbsp;</TD>\",";
		html += "\"<TD title=#008000 bgColor=#008000>&nbsp;</TD>\",";
		html += "\"<TD title=#008080 bgColor=#008080>&nbsp;</TD>\",";
		html += "\"<TD title=#0000FF bgColor=#0000FF>&nbsp;</TD>\",";
		html += "\"<TD title=#666699 bgColor=#666699>&nbsp;</TD>\",";
		html += "\"<TD title=#808080 bgColor=#808080>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF0000 bgColor=#FF0000>&nbsp;</TD>\",";
		html += "\"<TD title=#FF9900 bgColor=#FF9900>&nbsp;</TD>\",";
		html += "\"<TD title=#99CC00 bgColor=#99CC00>&nbsp;</TD>\",";
		html += "\"<TD title=#339966 bgColor=#339966>&nbsp;</TD>\",";
		html += "\"<TD title=#33CCCC bgColor=#33CCCC>&nbsp;</TD>\",";
		html += "\"<TD title=#3366FF bgColor=#3366FF>&nbsp;</TD>\",";
		html += "\"<TD title=#800080 bgColor=#800080>&nbsp;</TD>\",";
		html += "\"<TD title=#969696 bgColor=#969696>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF00FF bgColor=#FF00FF>&nbsp;</TD>\",";
		html += "\"<TD title=#FFCC00 bgColor=#FFCC00>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFF00 bgColor=#FFFF00>&nbsp;</TD>\",";
		html += "\"<TD title=#00FF00 bgColor=#00FF00>&nbsp;</TD>\",";
		html += "\"<TD title=#00FFFF bgColor=#00FFFF>&nbsp;</TD>\",";
		html += "\"<TD title=#00CCFF bgColor=#00CCFF>&nbsp;</TD>\",";
		html += "\"<TD title=#993366 bgColor=#993366>&nbsp;</TD>\",";
		html += "\"<TD title=#C0C0C0 bgColor=#C0C0C0>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF99CC bgColor=#FF99CC>&nbsp;</TD>\",";
		html += "\"<TD title=#FFCC99 bgColor=#FFCC99>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFF99 bgColor=#FFFF99>&nbsp;</TD>\",";
		html += "\"<TD title=#CCFFCC bgColor=#CCFFCC>&nbsp;</TD>\",";
		html += "\"<TD title=#CCFFFF bgColor=#CCFFFF>&nbsp;</TD>\",";
		html += "\"<TD title=#99CCFF bgColor=#99CCFF>&nbsp;</TD>\",";
		html += "\"<TD title=#CC99FF bgColor=#CC99FF>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFFFF bgColor=#FFFFFF>&nbsp;&nbsp;&nbsp;</TD>\"";
		html += "],";
		html += "EditorOnEndChangeBackgroundColor";
		html += ");";
		html += EDITOR_BACK_COLOR_CHOOSER_PREFIX + this.id + ".SetUserData(" + this.id + ");";
		html += EDITOR_BACK_COLOR_CHOOSER_PREFIX + this.id + ".Instantiate(" + this.id + ");";
		html += UtilEndScript();

		document.write(html);
	}

	function EditorFrameInstantiate(num, name, nform, frwidth, frheight)
	{
		if (this.instantiated) {
			return;
		}

		Zone[num] = name;
		ZForm = nform;
		NumZone += 1;
		this.textMode[name] = false;
		EDITOR_COMPOSITION_PREFIX = name;
		html = '';
		//html += "<tr>";
		//html += "<td>";
		html += "<iframe id=\"" + EDITOR_COMPOSITION_PREFIX + this.id + "\" width=\"" + frwidth + "\" height=\"" + frheight + "\" marginwidth=0 marginheight=0>";
		html += "</iframe>";
		//html += "</td>";
		//html += "</tr>";
		document.write(html);
	}
	
	function EditorColorPanelInstantiate()
	{
		if (this.instantiated) {
			return;
		}

		//html += "</table>";

		// Zone de choix de la couleur de caractères
		html = '';
		html += UtilBeginScript();
		html += "var " + EDITOR_FORE_COLOR_CHOOSER_PREFIX + this.id + " = new ColorChooser(";
		html += "editorIDGenerator,";
		html += "5, 8,";
		html += "[";
		html += "\"<TD title=#000000 bgColor=#000000>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#993300 bgColor=#993300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333300 bgColor=#333300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#003300 bgColor=#003300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#003366 bgColor=#003366>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#000080 bgColor=#000080>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333399 bgColor=#333399>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333333 bgColor=#333333>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#800000 bgColor=#800000>&nbsp;</TD>\",";
		html += "\"<TD title=#FF6000 bgColor=#FF6000>&nbsp;</TD>\",";
		html += "\"<TD title=#808000 bgColor=#808000>&nbsp;</TD>\",";
		html += "\"<TD title=#008000 bgColor=#008000>&nbsp;</TD>\",";
		html += "\"<TD title=#008080 bgColor=#008080>&nbsp;</TD>\",";
		html += "\"<TD title=#0000FF bgColor=#0000FF>&nbsp;</TD>\",";
		html += "\"<TD title=#666699 bgColor=#666699>&nbsp;</TD>\",";
		html += "\"<TD title=#808080 bgColor=#808080>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF0000 bgColor=#FF0000>&nbsp;</TD>\",";
		html += "\"<TD title=#FF9900 bgColor=#FF9900>&nbsp;</TD>\",";
		html += "\"<TD title=#99CC00 bgColor=#99CC00>&nbsp;</TD>\",";
		html += "\"<TD title=#339966 bgColor=#339966>&nbsp;</TD>\",";
		html += "\"<TD title=#33CCCC bgColor=#33CCCC>&nbsp;</TD>\",";
		html += "\"<TD title=#3366FF bgColor=#3366FF>&nbsp;</TD>\",";
		html += "\"<TD title=#800080 bgColor=#800080>&nbsp;</TD>\",";
		html += "\"<TD title=#969696 bgColor=#969696>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF00FF bgColor=#FF00FF>&nbsp;</TD>\",";
		html += "\"<TD title=#FFCC00 bgColor=#FFCC00>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFF00 bgColor=#FFFF00>&nbsp;</TD>\",";
		html += "\"<TD title=#00FF00 bgColor=#00FF00>&nbsp;</TD>\",";
		html += "\"<TD title=#00FFFF bgColor=#00FFFF>&nbsp;</TD>\",";
		html += "\"<TD title=#00CCFF bgColor=#00CCFF>&nbsp;</TD>\",";
		html += "\"<TD title=#993366 bgColor=#993366>&nbsp;</TD>\",";
		html += "\"<TD title=#C0C0C0 bgColor=#C0C0C0>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF99CC bgColor=#FF99CC>&nbsp;</TD>\",";
		html += "\"<TD title=#FFCC99 bgColor=#FFCC99>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFF99 bgColor=#FFFF99>&nbsp;</TD>\",";
		html += "\"<TD title=#CCFFCC bgColor=#CCFFCC>&nbsp;</TD>\",";
		html += "\"<TD title=#CCFFFF bgColor=#CCFFFF>&nbsp;</TD>\",";
		html += "\"<TD title=#99CCFF bgColor=#99CCFF>&nbsp;</TD>\",";
		html += "\"<TD title=#CC99FF bgColor=#CC99FF>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFFFF bgColor=#FFFFFF>&nbsp;&nbsp;&nbsp;</TD>\"";
		html += "],";
		html += "EditorOnEndChangeForegroundColor";
		html += ");";
		html += EDITOR_FORE_COLOR_CHOOSER_PREFIX + this.id + ".SetUserData(" + this.id + ");";
		html += EDITOR_FORE_COLOR_CHOOSER_PREFIX + this.id + ".Instantiate(" + this.id + ");";
		html += UtilEndScript();

		//zone de choix de la couleur de fond
		html += UtilBeginScript();
		html += "var " + EDITOR_BACK_COLOR_CHOOSER_PREFIX + this.id + " = new ColorChooser(";
		html += "editorIDGenerator,";
		html += "5, 8,";
		html += "[";
		html += "\"<TD title=#000000 bgColor=#000000>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#993300 bgColor=#993300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333300 bgColor=#333300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#003300 bgColor=#003300>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#003366 bgColor=#003366>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#000080 bgColor=#000080>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333399 bgColor=#333399>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#333333 bgColor=#333333>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#800000 bgColor=#800000>&nbsp;</TD>\",";
		html += "\"<TD title=#FF6000 bgColor=#FF6000>&nbsp;</TD>\",";
		html += "\"<TD title=#808000 bgColor=#808000>&nbsp;</TD>\",";
		html += "\"<TD title=#008000 bgColor=#008000>&nbsp;</TD>\",";
		html += "\"<TD title=#008080 bgColor=#008080>&nbsp;</TD>\",";
		html += "\"<TD title=#0000FF bgColor=#0000FF>&nbsp;</TD>\",";
		html += "\"<TD title=#666699 bgColor=#666699>&nbsp;</TD>\",";
		html += "\"<TD title=#808080 bgColor=#808080>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF0000 bgColor=#FF0000>&nbsp;</TD>\",";
		html += "\"<TD title=#FF9900 bgColor=#FF9900>&nbsp;</TD>\",";
		html += "\"<TD title=#99CC00 bgColor=#99CC00>&nbsp;</TD>\",";
		html += "\"<TD title=#339966 bgColor=#339966>&nbsp;</TD>\",";
		html += "\"<TD title=#33CCCC bgColor=#33CCCC>&nbsp;</TD>\",";
		html += "\"<TD title=#3366FF bgColor=#3366FF>&nbsp;</TD>\",";
		html += "\"<TD title=#800080 bgColor=#800080>&nbsp;</TD>\",";
		html += "\"<TD title=#969696 bgColor=#969696>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF00FF bgColor=#FF00FF>&nbsp;</TD>\",";
		html += "\"<TD title=#FFCC00 bgColor=#FFCC00>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFF00 bgColor=#FFFF00>&nbsp;</TD>\",";
		html += "\"<TD title=#00FF00 bgColor=#00FF00>&nbsp;</TD>\",";
		html += "\"<TD title=#00FFFF bgColor=#00FFFF>&nbsp;</TD>\",";
		html += "\"<TD title=#00CCFF bgColor=#00CCFF>&nbsp;</TD>\",";
		html += "\"<TD title=#993366 bgColor=#993366>&nbsp;</TD>\",";
		html += "\"<TD title=#C0C0C0 bgColor=#C0C0C0>&nbsp;&nbsp;&nbsp;</TD>\",";
		html += "\"<TD title=#FF99CC bgColor=#FF99CC>&nbsp;</TD>\",";
		html += "\"<TD title=#FFCC99 bgColor=#FFCC99>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFF99 bgColor=#FFFF99>&nbsp;</TD>\",";
		html += "\"<TD title=#CCFFCC bgColor=#CCFFCC>&nbsp;</TD>\",";
		html += "\"<TD title=#CCFFFF bgColor=#CCFFFF>&nbsp;</TD>\",";
		html += "\"<TD title=#99CCFF bgColor=#99CCFF>&nbsp;</TD>\",";
		html += "\"<TD title=#CC99FF bgColor=#CC99FF>&nbsp;</TD>\",";
		html += "\"<TD title=#FFFFFF bgColor=#FFFFFF>&nbsp;&nbsp;&nbsp;</TD>\"";
		html += "],";
		html += "EditorOnEndChangeBackgroundColor";
		html += ");";
		html += EDITOR_BACK_COLOR_CHOOSER_PREFIX + this.id + ".SetUserData(" + this.id + ");";
		html += EDITOR_BACK_COLOR_CHOOSER_PREFIX + this.id + ".Instantiate(" + this.id + ");";
		html += UtilEndScript();
		document.write(html);
	}

	function EditorFinalInstantiate(num)
	{
		if (this.instantiated) {
			return;
		}
		EDITOR_COMPOSITION_PREFIX = Zone[num];
		html = '';
		html += '<body style="font:10pt arial">';
		html += '</body>';
		eval(EDITOR_COMPOSITION_PREFIX + this.id).document.open();
		eval(EDITOR_COMPOSITION_PREFIX + this.id).document.write(html);
		eval(EDITOR_COMPOSITION_PREFIX + this.id).document.close();
		eval(EDITOR_COMPOSITION_PREFIX + this.id).document.designMode = "on";
		eval(EDITOR_COMPOSITION_PREFIX + this.id).document.onclick = new Function("EditorOnClick(" + this.id + "," + num + ")");
		eval(EDITOR_COMPOSITION_PREFIX + this.id).document.onfocus = new Function("EditorOnFocus(" + num + ")");
	}

	function EditorCloseInitSection()
	{
		if (this.instantiated) {
			return;
		}

		editorIDGenerator = null;
		this.instantiated = true;
	}

	function  EditorGetText()
	{
		return eval(EDITOR_COMPOSITION_PREFIX + this.id).document.body.innerText;
	}

	function  EditorSetText(text)
	{
		text = text.replace(/\n/g, "<br>");
		eval(EDITOR_COMPOSITION_PREFIX + this.id).document.body.innerHTML = text;
	}

	function  EditorGetHTML()
	{
		if (this.textMode[EDITOR_COMPOSITION_PREFIX]) {
			return eval(EDITOR_COMPOSITION_PREFIX + this.id).document.body.innerText;
		}
		EditorCleanHTML(this.id);
		EditorCleanHTML(this.id);
		return eval(EDITOR_COMPOSITION_PREFIX + this.id).document.body.innerHTML;
	}

	function  EditorSetHTML(html)
	{
		if (this.textMode[EDITOR_COMPOSITION_PREFIX]) {
			eval(EDITOR_COMPOSITION_PREFIX + this.id).document.body.innerText = html;
		}
		else {
			eval(EDITOR_COMPOSITION_PREFIX + this.id).document.body.innerHTML = html;
		}
	}

	function EditorGetBrief()
	{
		return this.brief;
	}

	function EditorSetBrief(brief)
	{
		this.brief = brief;
		var display = this.brief ? "none" : "inline";
		if (this.instantiated) {
			eval(EDITOR_PARAGRAPH_PREFIX + this.id).style.display = display;
			eval(EDITOR_LIST_AND_INDENT_PREFIX + this.id).style.display = display;
		}
	}

	function EditorOnCut(id)
	{
		EditorFormat(id, "cut");
	}

	function EditorOnCopy(id)
	{
		EditorFormat(id, "copy");
	}

	function EditorOnPaste(id)
	{
		EditorFormat(id, "paste");
	}

	function EditorOnBold(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "bold");
	}

	function EditorOnItalic(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "italic");
	}

	function EditorOnUnderline(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "underline");
	}

	function EditorOnAlignLeft(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "justifyleft");
	}

	function EditorOnCenter(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "justifycenter");
	}

	function EditorOnAlignRight(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "justifyright");
	}

	function EditorOnNumberedList(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "insertOrderedList");
	}

	function EditorOnBullettedList(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "insertUnorderedList");
	}

	function EditorOnDecreaseIndent(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "outdent");
	}

	function EditorOnIncreaseIndent(id)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "indent");
	}

	function EditorOnCreateHyperlink(id)
	{
		if (!EditorValidateMode(id)) {
			return;
		}
		var editor = editorMap[id];
		var anchor = EditorGetElement("A", eval(EDITOR_COMPOSITION_PREFIX + id).document.selection.createRange().parentElement());
		var link = prompt(STR_ENTER_LINK+": http://www.ovidentia.org) :", anchor ? anchor.href : "http://");
		if (link && link != "http://") {
			if (eval(EDITOR_COMPOSITION_PREFIX + id).document.selection.type == "None") {
				var range = eval(EDITOR_COMPOSITION_PREFIX + id).document.selection.createRange();
				//eval(EDITOR_COMPOSITION_PREFIX + id).document.focus();
				range.pasteHTML('<A HREF="' + link + '"></A>');
				range.select();
			}
			else {
				EditorFormat(id, "CreateLink", link);
			}
		}
	}

	//--------------------------//
	function EditorOnStartChangeForegroundColor(id)
	{
		if (eval(EDITOR_FORE_COLOR_CHOOSER_PREFIX + id).IsShowing()) {
			eval(EDITOR_FORE_COLOR_CHOOSER_PREFIX + id).Hide();
		}
		else {
			if(eval(EDITOR_BACK_COLOR_CHOOSER_PREFIX + id).IsShowing()) {
				eval(EDITOR_BACK_COLOR_CHOOSER_PREFIX + id).Hide(); }

			var editor = editorMap[id];
			editor.selectionRange = eval(EDITOR_COMPOSITION_PREFIX + id).document.selection.createRange();
			eval(EDITOR_FORE_COLOR_CHOOSER_PREFIX + id).Show(eval(EDITOR_FOREGROUND_BUTTON_PREFIX + id).style.left, eval(EDITOR_FOREGROUND_BUTTON_PREFIX + id).style.top);
		}
	}

	function EditorOnEndChangeForegroundColor(color, id)
	{
	    if (!EditorValidateMode(id)) {
		return;
	    }
	    var editor = editorMap[id];
	    var bodyRange = eval(EDITOR_COMPOSITION_PREFIX + id).document.body.createTextRange();
	    if (bodyRange.inRange(editor.selectionRange)) {
				editor.selectionRange.select();
				if (color) {
					EditorFormat(id, "forecolor", color);
				}
				else {
					eval(EDITOR_COMPOSITION_PREFIX + id).focus();
				}
	    }
	    else {
			editor.selectionRange.collapse(false);
			editor.selectionRange.select();
			if (color) {
				EditorFormat(id, "forecolor", color);
			}
			else {
				eval(EDITOR_COMPOSITION_PREFIX + id).focus();
			}
	    }
	}


	function EditorOnStartChangeBackgroundColor(id)
	{
		if (eval(EDITOR_BACK_COLOR_CHOOSER_PREFIX + id).IsShowing()) {
			eval(EDITOR_BACK_COLOR_CHOOSER_PREFIX + id).Hide();
		}
		else {
			if (eval(EDITOR_FORE_COLOR_CHOOSER_PREFIX + id).IsShowing()) {
				eval(EDITOR_FORE_COLOR_CHOOSER_PREFIX + id).Hide();} 

			var editor = editorMap[id];
			editor.selectionRange = eval(EDITOR_COMPOSITION_PREFIX + id).document.selection.createRange();
			eval(EDITOR_BACK_COLOR_CHOOSER_PREFIX + id).Show(eval(EDITOR_BACKGROUND_BUTTON_PREFIX + id).style.left, eval(EDITOR_BACKGROUND_BUTTON_PREFIX + id).style.top);
		}
	}

	function EditorOnEndChangeBackgroundColor(color, id)
	{
	    if (!EditorValidateMode(id)) {
		return;
	    }
	    var editor = editorMap[id];
	    var bodyRange = eval(EDITOR_COMPOSITION_PREFIX + id).document.body.createTextRange();
	    if (bodyRange.inRange(editor.selectionRange)) {
				editor.selectionRange.select();
				if (color) {
					EditorFormat(id, "backcolor", color);
				}
				else {
					eval(EDITOR_COMPOSITION_PREFIX + id).focus();
				}
	    }
	    else {
			editor.selectionRange.collapse(false);
			editor.selectionRange.select();
			if (color) {
				EditorFormat(id, "backcolor", color);
			}
			else {
				eval(EDITOR_COMPOSITION_PREFIX + id).focus();
			}
	    }
	}
	//-----------------------//
	function EditorOnParagraph(id, select)
	{
		EditorFormat(id, "formatBlock", select[select.selectedIndex].value);
		select.selectedIndex = 0;
			
	}

	function EditorOnFont(id, select)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "fontname", select[select.selectedIndex].value);
		select.selectedIndex = 0;
	}

	function EditorOnSize(id, select)
	{
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		EditorFormat(id, "fontsize", select[select.selectedIndex].value);
		select.selectedIndex = 0;
	}

	function EditorOnViewHTMLSource(id, textMode)
	{
		var editor = editorMap[id];
		editor.textMode[EDITOR_COMPOSITION_PREFIX] = textMode;
		if (editor.textMode[EDITOR_COMPOSITION_PREFIX]) {
			EditorCleanHTML(id);
			EditorCleanHTML(id);
			eval(EDITOR_COMPOSITION_PREFIX + id).document.body.innerText = eval(EDITOR_COMPOSITION_PREFIX + id).document.body.innerHTML;
		}
		else {
			eval(EDITOR_COMPOSITION_PREFIX + id).document.body.innerHTML = eval(EDITOR_COMPOSITION_PREFIX + id).document.body.innerText;
		}
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
	}

	function EditorOnClick(id, num)
	{
		EDITOR_COMPOSITION_PREFIX = Zone[num];
		for ( k=0 ; k<document.forms.length ; k++ )
		{
			if( document.forms[k].name == ZForm )
			{
				break;
			}
		}	

		if(editor.textMode[EDITOR_COMPOSITION_PREFIX]) {
			eval(EDITOR_TOP_TOOLBAR_PREFIX + id).document.forms[k].elements['editorCheckbox'].checked = true;
		}
		else {
			eval(EDITOR_TOP_TOOLBAR_PREFIX + id).document.forms[k].elements['editorCheckbox'].checked = false;
		}
		eval(EDITOR_BACK_COLOR_CHOOSER_PREFIX + id).Hide();
		eval(EDITOR_FORE_COLOR_CHOOSER_PREFIX + id).Hide();
	}
		
	function EditorOnFocus(num)
	{
		EDITOR_COMPOSITION_PREFIX = Zone[num];
	}

	function EditorValidateMode(id)
	{
		var editor = editorMap[id];
		if (!editor.textMode[EDITOR_COMPOSITION_PREFIX]) {
			return true;
		}
		alert(STR_CONFIRM);
		eval(EDITOR_COMPOSITION_PREFIX + id).focus();
		return false;
	}

	function EditorFormat(id, what, opt)
	{
		if (!EditorValidateMode(id)) {
			return;
		}
		if (opt == "removeFormat") {
			what = opt;
			opt = null;
		}
		if (opt == null) {
			eval(EDITOR_COMPOSITION_PREFIX + id).document.execCommand(what);
		}
		else {
			eval(EDITOR_COMPOSITION_PREFIX + id).document.execCommand(what, "", opt);
		}
	}

	function EditorCleanHTML(id)
	{
		var fonts = eval(EDITOR_COMPOSITION_PREFIX + id).document.body.all.tags("FONT");
		for (var i = fonts.length - 1; i >= 0; i--) {
			var font = fonts[i];
			if (font.style.backgroundColor == "#ffffff") {
				font.outerHTML = font.innerHTML;
			}
		}
	}

	function EditorGetElement(tagName, start)
	{
		while (start && start.tagName != tagName) {
			start = start.parentElement;
		}
		return start;
	}

/*function Switch() {
    if (editor.GetText() != "" && editor.GetText() != editor.GetHTML()) {
    conf = confirm("Ceci convertira votre message en format texte. Toutes les dispositions d'affichage seront perdues. Souhaitez-vous continuer ? ");
    if (!conf) return;
  }
  document.Compose.content.value = editor.GetText();
    document.Compose.action = document.Compose.action + "&SWITCH=1";
  document.Compose.submit();
}*/

function document.body.onload() {
  //editor.SetHTML(document.all.plainmsg.innerHTML);
	for ( k=0 ; k<document.forms.length ; k++ )
	{
		if( document.forms[k].name == ZForm )
		{
			break;
		}
	}	

	for ( i=0 ; i<NumZone ; i++ )
	{
		EDITOR_COMPOSITION_PREFIX = Zone[i];
		editor.SetHTML(document.forms[k].elements[Zone[i]].value);
  	}

}

function SetVals() {
		//document.Compose.content.value = editor.GetHTML();

	for ( k=0 ; k<document.forms.length ; k++ )
	{
		if( document.forms[k].name == ZForm )
		{
			break;
		}
	}
	for ( i=0 ; i<NumZone ; i++ )
	{
		EDITOR_COMPOSITION_PREFIX = Zone[i];
		document.forms[k].elements[Zone[i]].value = editor.GetHTML();
	}
		
}

/*function RecordAttachments(Files, File0, File1, File2) {
  window.document.Compose.elements["File0Data"].value = File0;
  window.document.Compose.elements["File1Data"].value = File1;
  window.document.Compose.elements["File2Data"].value = File2;
    window.document.all.Atts.innerText = Files;
	}

var remote=null;
function rs(n,u,w,h,x) {
  args="width="+w+",height="+h+",resizable=yes,scrollbars=yes,status=0";
  remote=window.open(u,n,args);
  if (remote != null) {
    if (remote.opener == null)
      remote.opener = self;
  }
  if (x == 1) { return remote; }
}*/

var awnd=null;
/*function ScriptAttach() {
  f0 = escape(document.Compose.elements["File0Data"].value);
  f1 = escape(document.Compose.elements["File1Data"].value);
  f2 = escape(document.Compose.elements["File2Data"].value);
  fname = escape(document.Compose.elements["FName"].value);
  awnd=rs('att','/ym/Attachments?YY=85370&File0Data='+f0+'&File1Data='+f1+'&File2Data='+f2+'&FName='+fname,450,600,1);
  awnd.focus();
}

var sigAttMap = [false];

function OnFromAddrChange()
{
    var i = document.Compose.FromAddr.selectedIndex;
    if (i >= 0 && i < sigAttMap.length) {
	document.all.SA.checked = sigAttMap[i];
    }
}*/