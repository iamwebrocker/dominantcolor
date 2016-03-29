<?php

// helper function for color contrast,
// see: http://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php
function lumdiff($col1,$col2){
	if(!isset($col1) || !isset($col2)){
		return false;
	} else {
		$R1 = $col1[0];
		$G1 = $col1[1];
		$B1 = $col1[2];

		$R2 = $col2[0];
		$G2 = $col2[1];
		$B2 = $col2[2];

		$L1 = 0.2126 * pow($R1/255, 2.2) +
			  0.7152 * pow($G1/255, 2.2) +
			  0.0722 * pow($B1/255, 2.2);

		$L2 = 0.2126 * pow($R2/255, 2.2) +
			  0.7152 * pow($G2/255, 2.2) +
			  0.0722 * pow($B2/255, 2.2);

		if($L1 > $L2){
			return ($L1+0.05) / ($L2+0.05);
		}else{
			return ($L2+0.05) / ($L1+0.05);
		}
	}
}
// helper func, see if string matches hex criteria
function isHex($str){
	if(isset($str) && $str != ''){
		$test = preg_match("/[^0-9A-Fa-f]/",$str);
		if($test === 0){
			return true;
		}
	}
	return false;
}
//
function getColoredPlaceholder($imgColor){
	if(!isset($imgColor) || !isHex($imgColor) ){
		return false;
	}
	$header                  = pack('H*',"474946383961");
	$logicalScreenDescriptor = pack('H*',"01000100800100");
	$imageColor              = pack('H*',$imgColor);
	$colorPad                = pack('H*',"000000");
	$imageDescriptor         = pack('H*',"2c000000000100010000");
	$imageData               = pack('H*',"0202440100");

	$binary  = $header . $logicalScreenDescriptor . $imageColor . $colorPad . $imageDescriptor . $imageData;
	$dataUrl = 'data:image/gif;base64,'.base64_encode($binary);

	return $dataUrl;
}
// image to show if no image is given thru form
$img = 'https://www.webrocker.de/blog/wp-content/uploads/2016/02/image-26-530x398.png';
if(isset($_POST['img']) && $_POST['img'] != ''){
	$img = $_POST['img'];
}

// php adapt of: https://manu.ninja/dominant-colors-for-lazy-loading-images
$image = new Imagick($img);
$w = $image->width;
$h = $image->height;
$aspectRatio = ($h/$w);
$image->resizeImage(150, 150, Imagick::FILTER_GAUSSIAN, 1);
$image->quantizeImage(1, Imagick::COLORSPACE_LAB, 0, false, false);
$image->setFormat('RGB');

$hexImg = bin2hex($image);
$imgColor = substr($hexImg, 0, 6);  // ee ff cc
$r = hexdec(substr($hexImg, 0, 2)); // ee -> 238
$g = hexdec(substr($hexImg, 2, 2)); // ff -> 255
$b = hexdec(substr($hexImg, 4, 2)); // cc -> 204

$textColor   = '#FFF';
$borderColor = '#CCC';
$inversColor = '#333';
$contrast = lumdiff(array($r,$g,$b),array(0,0,0));
if($contrast >= 5){
	$textColor   = '#000';
	$borderColor = '#333';
	$inversColor = '#CCC';
}

$dataUrl = getColoredPlaceholder($imgColor);

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Dominant Color in Image</title>
		<style>
			body {
				margin: 2rem auto;
				padding: 2rem;
				width: 80vw;
				max-width: 30em;
				font-family: Arial, Verdana, sans-serif;
				background-color: <?php echo '#'.$imgColor;?>;
				color: <?php echo $textColor; ?>;
			}
			.item-wrap {
				margin: 0;
				max-width: 100%;
			}
			.image-wrap {
				width: 100%;
				position: relative;
			}
			.image-wrap .item-image {
				position: absolute;
				top: 0; left: 0;
				width: 100%;
				height: 100%;
			}
			.item-image {
				display: block;
			}
			.item-info {
				margin: 0.5rem 0;
				text-align: center;
			}
			.lazyload,
			.lazyloading {
				opacity: 0;
			}
			.lazyloaded {
				opacity: 1;
				transition: opacity 2000ms;
			}
			.form {
				display: flex;
				flex-flow: row wrap;
				justify-content: stretch;
			}
			.form label,
			.form input,
			.form button {
				flex: 1 0 auto;
			}
			.form label {
				font-size: 90%;
				color: <?php echo $borderColor; ?>;
				align-self: center;
			}
			.form input {
				flex-grow: 2;
				width: 50%;
			}
			.form input,
			.form button {
				padding: 0.25rem;
				border: 1px solid <?php echo $borderColor; ?>;
				background-color: #eee;
				color: #333;
			}
			.form button {
				background-color: <?php echo $borderColor; ?>;
				color: <?php echo $inversColor; ?>;
			}
			.form input:hover,
			.form input:focus,
			.form input:active {
				background-color: #fff;
				color: #000;
			}
			.form button:hover,
			.form button:focus,
			.form button:active {
				background-color: <?php echo $inversColor; ?>;
				color: <?php echo $borderColor; ?>;
			}
		</style>
	</head>
	<body>
		<?php
		echo '
			<figure class="item-wrap">
				<div class="image-wrap" style="padding-top: ' . 100*$aspectRatio . '%;" data-ar="' . $aspectRatio . '">
					<img class="item-image lazyload" width="' . $w . '" height="' . $h . '" src="' . $dataUrl . '" data-src="' . $img . '">
				</div>
				<figcaption class="item-info">
					This image\'s dominant color is: <strong>#' . $imgColor . '</strong>
				</figcaption>
			</figure>';
		?>
		<form class="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" >
			<label for="img">Image-URL</label><input type="url" id="img" name="img" value="<?php echo $img; ?>" required>
			<button>Go</button>
		</form>
		<script src="js/lazysizes.min.js" async=""></script>
	</body>
</html>