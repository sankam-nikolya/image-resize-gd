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
// File name and quality , same image type as source.
// Quality should be an integer <0; 100>
$fileName = $image->saveImageFile('test-files/output/test2', 90);

//File name, quality and output type.
$fileName = $image->saveImageFile('test-files/output/test2', 90, IMAGETYPE_PNG);

//File name, quality, output type, solid background color.
$fileName = $image->saveImageFile('test-files/output/test2', 90, IMAGETYPE_PNG, 'FF0000');

```

