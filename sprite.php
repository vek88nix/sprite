<?php
/**
 * Example of class 'sprite' usage
 *
 * @author  vek88nix ( vek88nix@gmail.com, twitter.com/vek88nix, brainstorage.me/vek88nix )
 */

/**
 * creating object of class 'sprite'
 */
$sprite = new sprite();

/**
 * setting up root path
 */
$sprite->setRootPath('/var/www/');
/**
 * setting up relative path (dir path to css file)
 */
$sprite->setRelativePath('/var/www/css/');

/**
 * setting up CSS Sprite Path (will be used in css url())
 */
$sprite->setCssSpritePath('../images/sprite.png');

/**
 * setting up real physical sprite file path
 */
$sprite->setSpritePath('/var/www/images/sprite.png');

/**
 * Loading CSS content
 */
$sprite->loadCSS('/var/www/css/screen.css');


/**
 * Getting images from css
 */
$arImages = $sprite->getImages();

/**
 * Preparing paths for makeSprite()
 * @todo make a simple function for this in class.
 */
foreach ($arImages AS &$strImage) {
	$strImage = array(
		'csspath' => $strImage,
		'filepath' => $sprite->getImagePath($strImage)
	);
}
unset($strImage);

/**
 * Making a file sprite
 *
 * function must return a array with background positions :)
 */
$arImages = $sprite->makeSprite($arImages);

/**
 * Preparing CSS (replacing)
 */
$sprite->prepareCSS($arImages);

/**
 * Saving new css to the file
 */
$sprite->saveCSS('/var/www/css/new-screen.css');

//end of file sprite.php