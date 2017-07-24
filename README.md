# video-thumbnail-sprite

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/emgag/video-thumbnail-sprite.svg?style=flat-square)](https://packagist.org/packages/emgag/video-thumbnail-sprite)
[![Project Status: Active - The project has reached a stable, usable state and is being actively developed.](http://www.repostatus.org/badges/0.1.0/active.svg)](http://www.repostatus.org/#active)

PHP library for generating video thumbnail sprites to be used for thumbnails in [JWPlayer](http://support.jwplayer.com/customer/portal/articles/1407439-adding-preview-thumbnails)'s seek bar. For a real world example used in production, see [gameswelt.de](http://www.gameswelt.de/the-witcher-3-wild-hunt/test/multipler-rollenspielorgasmus,238958).

## System requirements

Tested with >=5.5, following binaries need to be installed

* [ffmpeg](http://www.ffmpeg.org/download.html) (tested with v2.2) or [ffmpegthumbnailer](https://github.com/dirkvdb/ffmpegthumbnailer)
* [imagemagick](http://www.imagemagick.org/script/binary-releases.php) (tested with v6.6)

## Installation

```
composer require emgag/video-thumbnail-sprite
```

## Usage

```PHP
use Emgag\Video\ThumbnailSprite\ThumbnailSprite;


$sprite = new ThumbnailSprite();
$sprite->setSource('path-to-source-video.mp4')
       ->setOutputDirectory('dir-to-store-sprite-and-vtt')
       ->setPrefix('sprite') // filename prefix for image sprite and WebVTT file (defaults to "sprite", resulting in "sprite.jpg" and "sprite.vtt")
       ->setUrlPrefix('http://example.org/sprites') // absolute URL of sprite image or relative to where the WebVTT file is stored
       ->setRate(10) // sampling rate in seconds
       ->setMinThumbs(20) // minimum number of images (will modify sampling rate accordingly if it would result in fewer images than this)
       ->setWidth(120) // width of one thumbnail in px
       ->generate();
```

in new version it use ffmpegthumbnailer as default convertor. it is faster than ffmpeg.
if you want to use ffmpeg should call setConvertor function:

```PHP
$sprite->setConvertor('ffmpeg');

```

and if you want keep main images of sprite should:

```PHP
$sprite->keepMainImages()
       ->setOutputImageDirectory('dir-to-store-images');
```


##Acknowledgments

Inspired by [vlanard/videoscripts](https://github.com/vlanard/videoscripts) and [scaryguy/jwthumbs](https://github.com/scaryguy/jwthumbs).

Uses:

* [captioning/captioning](https://github.com/captioning/captioning)
* [emgag/flysystem-tempdir](https://github.com/emgag/flysystem-tempdir)
* [intervention/image](https://github.com/Intervention/image)
* [php-ffmpeg/php-ffmpeg](https://github.com/PHP-FFMpeg/PHP-FFMpeg)
* [symfony/process](https://github.com/symfony/Process)

## License

video-thumbnail-sprite is licensed under the [MIT License](http://opensource.org/licenses/MIT).
