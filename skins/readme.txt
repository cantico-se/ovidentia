******************************************************************************

                     OVIDENTIA SKIN

http://www.ovidentia.org                                    Ovidentia Community
*******************************************************************************
1 - INTRODUCTION
2 - HOW TO CUSTOMIZE A SKIN
3 - HOW TO CREATE A NEW SKIN

1 - INTRODUCTION:
Ovidentia skin is a group of templates files, css files and images which 
gives a particular look to an Ovidentia web site.
Templates files, called 'Ovidentia templates', are ordinary text files
that contains HTML tags intersepted with specifics Ovidentia tags.
Ovidentia parse template file and replace Ovidentia tags with values.
The idea behind this is to let developper concentrate only on PHP code and, 
web designer can edit template file in a WYSIWYG tool.
Also html developpers can offers differents skins by modifying templates only.

Skins are differentiated by their names.
Each skin can have multiple css styles files that give different looks to Ovidentia
Web Site.
Ovidentia comes with a default skin: Ovidentia.
( Other skins can be downloaded from www.ovidentia.org site )
You can find original skins under ovidentia/skins folder in the distribution.


2 - HOW TO CUSTOMIZE A SKIN:
Sometimes you need to customize one or more templates files for a given skin.
This is the case if you want to change top-left, top-right, bottom-left, bottom-right
and banner images which are defined in config.html.

You must proceed as follows:

	- In the same folder as config.php, create a folder ( if doesn't exist )
	skins/name-of-skin/templates where name-of-skin is the name of the skin that
	you want to customize. We refer to this folder as 'template customization folder'

	- Copy original templates files which you want to customize in 'template customization folder'.
	( For example config.html file. See below variables explanations used in this file )
	You don't need to remove or rename original templates files, Ovidentia
	always use templates files in 'template customization folder'.If a template file doesn't exist
	it will use original template file.

	- Start modifying those files.

3 - HOW TO CREATE A NEW SKIN:

The purpose of this part is to show you how to create new skins.

Always start with an original skin ( Ovidentia skin for example ).
Give a name to your skin and create a folder that have the same name
as your skin. Under this folder create three folders: templates, styles and images.

In template folder copy templates files from original skin. Proceed in the same way for
images and styles.
Start modifying templates files, styles files and add or remove images to suit your need.
Attention, don't change templates files names, neither specifics Ovidentia tags in template file.
To help you understanding templates files and Ovidentia tags syntax see below.

Ovidentia Tags syntax:

template file can contain multiple templates.
Each template is delimited and recognized by specifics tags: template Tags.
The syntax is of the form:

    <!--begin template-name -->
    ....
    <!--end template-name -->

where template-name is the name of your template.
( Remarque: there is a blank space before --> )


The syntax used to indicate text substitution is of the form:

    { var1 }

var1 identifies a variable named var1 in PHP code. This tag is used to substitute
variable data into text.

As example look at this template:

    <b>I say: <font color=red>{ var1 }</font>

If in PHP code, var1 contains "Hello Word!", this will output:

    <b>I say: <font color=red>Hello Word!</font>
    
Sometimes, the text to be included is dependent upon some data. The if tag
is provided to support insertion of text based on variable. An expression is evaluated
and if the value is true, the text is inserted, otherwise nothing happen.
The if syntax is as follows:

    <!--#if var1 "expresion" -->
    .....

    <!--#else var1 -->
    .....
    <!--#endif var1 -->

( Remarque: always put a blank before --> )

where expression is one of the following forms:

    - == value
    - != value
    - >= value
    - <= value
    - > value
    - < value

For example:

    <!--#if lang "== english" -->
        <b>I say: <font color=red>Hello World</font>
    <!--#else lang -->
        <b>Je dis: <font color=red>Bonjour tout le monde</font>
    <!--#endif lang -->

will output, if lang == "english"

    <b>I say: <font color=red>Hello World</font>

otherwise

    <b>Je dis: <font color=red>Bonjour tout le monde</font>

You can also test a variable with true/false like this

    <!--#if bClosed -->
        <b>bClosed variable is true</font>
    <!--#endif bClosed -->


Commenly, it is necessary to insert a sequence of text, like table rows or
select list from an sql request for example. For this, you use in tag:

    <!--in function-to-call -->
    .....
    <!--endin function-to-call -->

where function-to-call is the name of the function to call.
At each call, if the function return 'true',  variable substitution is made
and result is printed, otherwise noting happen.



Ovidentia variables:
{ babSkinPath } :
	always contains path to current ORIGINAL skin ( i.e babInstalPath/skins/babSkin/
	path where 'babInstallPath' is a relative path to ovidentia php sources as indicated 
	in config.php and 'babSkin' is the name of the current skin )

{ babStyle } :
	contains the name of the current style file ( with extension )

{ babLanguage } :
	current language used


Each a new Ovidentia release comes with templates changes.
By this you can update your skin through Ovidentia evolution.

You can browse documentations here : 
	http://www.ovidentia.org

If you have any comment or remarks please send an email to: community@ovidentia.org

http://www.ovidentia.org                                    Ovidentia Community
*******************************************************************************
