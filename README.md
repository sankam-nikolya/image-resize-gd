ImageResizeGD
=======

Simple PHP class for resizing and compressing images in PNG, JPEG and GIF format.
Refer to method comments for usage details.
Requires GD library.

No copyrights. Feel free to use this the way you like.


Usage guide
-----------
Create new instance.
```
$image = new ImageResizeGD('test-files/test2.png');

```
Resize image by height maintaining the aspect ratio.
```
$image->resizeByHeight(1080);

```
Resize image by width maintaining the aspect ratio.
```
$image->resizeByWidth(1920);

```
Resize image, so the output does not exceed given width and height.
Smaller image will be upscaled.
Method maintains the aspect ratio.
```
$image->resizeWithinDimensions(300, 1000);

```
Resize image, so the output matches exactly given dimensions. Image is scaled, centered and cropped.
```
$image->resizeToFillDimensionsExactly(800, 600);

```
Save new image.
```
// File name only, outputs same image type as source, default quality 80 for IMAGETYPE_JPEG, 9 for IMAGETYPE_PNG.
$fileName = $image->saveImageFile('test-files/output/test2_output');

// File name, output type and quality.
// Quality should be an integer <0; 100> for IMAGETYPE_JPEG
// Quality should be an integer <0; 9> for IMAGETYPE_PNG
$fileName = $image->saveImageFile('test-files/output/test2_output', IMAGETYPE_JPEG, 90);

// File name, output type, quality and solid background color.
$fileName = $image->saveImageFile('test-files/output/test2_output', IMAGETYPE_JPEG, 90, 'FF0000');
```
Example
-----------
```
$image = new ImageResizeGD('test-files/test2.png');
$image->resizeToFillDimensionsExactly(800, 600);
$fileName = $image->saveImageFile('test-files/output/test2_output');
```

