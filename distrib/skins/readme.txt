skins/

This folder let you customize skins. To do this:

- Create a folder with the same name as the skin you want to customize
- Under this folder create 'templates', 'styles' and 'images' folders
- From the original skin in skins/ovidentia folder, copy in 'templates' templates files that you want to change
- From the original skin in skins/ovidentia folder, copy in 'styles' css file that you want to customize
  ( don't forgot to update path to css files in customized config.html )
- In 'images' add images files that you want to use in your site

ATTENTION:
In config.html, babSkinPath variable refer ALWAYS to babInstalPath/skins/babSkin/ path,
where babSkinPath is a relative path to ovidentia php sources as indicated in config.php
and babSkin is the name of the skin.

You can browse documentationshere : 
	-Installation Guide : http://ovidentia.koblix.org/documentation/ig-en.html 
	-Developper Guide : http://ovidentia.koblix.org/documentation/dg-en.html 
	-User Guide : http://ovidentia.koblix.org/documentation/ug-en.html

If you have any comment or remarks please send an email to community@ovidentia.org