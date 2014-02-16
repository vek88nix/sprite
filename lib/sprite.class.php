<?php
/**
 * Class sprite
 *
 * @version 1.0
 * @author  vek88nix ( vek88nix@gmail.com, twitter.com/vek88nix, brainstorage.me/vek88nix )
 * @todo    create a minimal picture size
 */

class sprite {
	private $strRootPath = null;
	private $strRelativePath = null;

	private $cssContent = null;
	private $arImages = array();

	private $strCssSpritePath = null;
	private $strSpritePath = null;
	private $intSpritePadding = 10;

	private $maxImageSize = 400;
	/**
	 * Loading css file to $cssContent variable
	 *
	 * @param $filepath
	 *
	 * @return bool
	 */
	public function loadCSS($filepath) {
		$this->cssContent = @file_get_contents($filepath);
		return (bool)$this->cssContent;
	}

	/**
	 * Path to sprite file (for save)
	 *
	 * @param $path path to sprite
	 */
	public function setSpritePath($path) {
		$this->strSpritePath = $path;
	}

	/**
	 * For css.
	 * Will be used in css; example:
	 * background: url('$strCssSpritePath');
	 *
	 * @param $path path to sprite (for css)
	 */
	public function setCssSpritePath($path) {
		$this->strCssSpritePath = $path;
	}

	/**
	 * Setting ROOT path, for example file begins with slash:
	 * /wp-content/images/image.png
	 * will be overwrited to
	 * $strRootPath/wp-content/images/image.png
	 *
	 * @param $path
	 */
	public function setRootPath($path) {
		$this->strRootPath = $path;
	}

	/**
	 * Setting RELATIVE path, for example file begins with dot(s):
	 * ./images/image.png
	 * ../img/aaa.jpeg
	 * will be overwrited to
	 * $strRelativePath/images/image.png
	 * $strRelativePath/../img/aaa.jpeg
	 *
	 * @param $path
	 */
	public function setRelativePath($path) {
		$this->strRelativePath = $path;
	}

	/**
	 * Scanning CSS content for images (not gif!)
	 *
	 * @return array|int -1 = error; 0 = empty; array = success
	 */
	public function scanImages() {
		$preg = @preg_match_all('/url\(\s*[\'"]?(\S*\.(?:jpe?g|png))[\'"]?\s*\)[^;}]*?/i', $this->cssContent, $matches);

		if (!$preg) {
			return -1;
		}

		if (!isset($matches) || !isset($matches[1])) {
			return 0;
		}

		return $this->arImages = (array)$matches[1];
	}

	/**
	 * Get array of images
	 *
	 * @return array|int
	 */
	public function getImages() {
		if (!$this->arImages) {
			return $this->scanImages();
		}
		return $this->arImages;
	}


	/**
	 * Get real image path
	 *
	 * @param $path string path to image
	 *
	 * @return string
	 */
	public function getImagePath($path) {
		$sLetter = $path[0];
		if ($sLetter == '/') {
			$path = rtrim($this->strRootPath, '/') . '/' . ltrim($path, '/');
		}
		elseif (parse_url($path, PHP_URL_HOST)) {
			//url? what we must do?

		}
		else {
			//action with dots or simple path
			$path = rtrim($this->strRelativePath, '/') . '/' . ltrim($path, '/');
		}

		return $path;
	}

	/**
	 * @param $arImages array of images
	 *
	 * @return array of images and new bg position
	 */
	public function makeSprite($arImages) {

		foreach ($arImages AS &$arItem) {



			$size = getimagesize($arItem['filepath']);
			if($size[0] > $this->maxImageSize || $size[1] > $this->maxImageSize){
				unset($arItem);
				continue;
			}

			$extension = strtolower(end(explode('.', $arItem['filepath'])));
			switch ($extension) {
				case 'jpeg':
				case 'jpg':
					$resource = @imagecreatefromjpeg($arItem['filepath']);
					break;

				case 'png':
				default:
					$resource = @imagecreatefrompng($arItem['filepath']);
					break;
			}

			$arItem['resource'] = $resource;
			$arItem['size'] = array(
				'width' => $size[0],
				'height' => $size[1]
			);

		}


		$maxWidth = 0;
		$height = 0;
		foreach ($arImages AS $image) {
			if ($image['size']['width'] > $maxWidth)
				$maxWidth = $image['size']['width'];

			$height += $image['size']['height'];
		}

		$height += $this->intSpritePadding * (count($arImages) - 1);

		$sprite = imagecreatetruecolor($maxWidth, $height);

		imagesavealpha($sprite, true);
		$trans_colour = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
		imagefill($sprite, 0, 0, $trans_colour);

		$x = 0;
		$y = 0;


		foreach ($arImages AS $number => $image) {
			imagecopy($sprite, $image['resource'], $x, $y, 0, 0, $image['size']['width'], $image['size']['height']);

			$arImages[$number]['position'] = array(
				'left' => $x,
				'top' => $y
			);

			unset($arImages[$number]['resource']);

			$y += $image['size']['width'] + $this->intSpritePadding;
		}

		imagepng($sprite, $this->strSpritePath);

		return $arImages;
	}


	public function prepareCSS($arImages) {
		$lines = explode("\n", $this->cssContent);
		foreach ($lines AS &$line) {
			foreach ($arImages AS $image) {
				if (strstr($line, $image['csspath'])) {
					$line = str_replace($image['csspath'], $this->strCssSpritePath, $line);
					$begins = "";
					$i = 0;
					do {
						$letter = substr($line, $i, 1);
						$i++;
						$space = in_array($letter, array(
							" ",
							"\t"
						));
						if ($space) {
							$begins .= $letter;
						}
					} while ($space);
					$line .= "\n{$begins}";
					$line .= "background-position: {$image['position']['left']}px {$image['position']['top']}px;";

					break;
				}
			}
		}

		$content = implode("\n", $lines);
		$this->cssContent = $content;
		unset($lines);
	}

	/**
	 * Saving css
	 * @param $filename
	 *
	 * @return bool
	 */
	public function saveCSS($filename){
		return (bool)@file_put_contents($filename, $this->cssContent);
	}
}

//end of file 'sprite.class.php'