# video-thumbnail-sprite

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Project Status: Wip - Initial development is in progress, but there has not yet been a stable, usable release suitable for the public.](http://www.repostatus.org/badges/0.1.0/wip.svg)](http://www.repostatus.org/#wip)

PHP library for generating video thumbnail sprites to be used for thumbnails in [JWPlayer](http://support.jwplayer.com/customer/portal/articles/1407439-adding-preview-thumbnails)'s seek bar. For a real world example used in production, see [gameswelt.de](http://www.gameswelt.de/the-witcher-3-wild-hunt/test/multipler-rollenspielorgasmus,238958).

WIP, doesn't work so far & nothing to see here atm

## System requirements

Tested with >=5.5, following binaries need to be installed

* [ffmpeg](http://www.ffmpeg.org/download.html) (tested with v2.2)
* [imagemagick](http://www.imagemagick.org/script/binary-releases.php) (tested with v6.6)

## Installation

```
composer require emgag/video-thumbnail-sprite
```

## Usage

```PHP
use Emgag\Video\ThumbnailSprite\ThumbnailSprite;


$sprite = new ThumbnailSprite();
$sprite->setSource('path-to-source-video.mp4');
$sprite->setOutputDirectory('dir-to-store-sprite-and-vtt');
// filename prefix for image sprite and WebVTT file (defaults to "sprite", resulting in "sprite.jpg" and "sprite.vtt")
$sprite->setPrefix('sprite');
// absolute URL of sprite image or relative to where the WebVTT file is stored
$sprite->setUrlPrefix('http://example.org/sprites');
// sampling rate in seconds
$sprite->setRate(10);
// minimum number of images (will modify sampling rate accordingly if it would result in fewer images than this)
$sprite->setMinThumbs(20);
// width of one thumbnail in px
$sprite->setWidth(120);
// write sprite and vtt file
$sprite->write();
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
