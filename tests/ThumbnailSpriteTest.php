<?php

use Emgag\Video\ThumbnailSprite\Thumbnailer\Ffmpeg;
use Emgag\Video\ThumbnailSprite\Thumbnailer\FfmpegThumbnailer;
use Emgag\Video\ThumbnailSprite\Thumbnailer\ThumbnailerInterface;
use Emgag\Video\ThumbnailSprite\ThumbnailSprite;
use GuzzleHttp\Client;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ThumbnailSpriteTest extends TestCase
{

    /**
     * @var Filesystem
     */
    public $outputFS;

    /**
     * @var array
     */
    public $testData = [
        'src'    => __DIR__ . '/test_data/bbb_sunflower_1080p_30fps_normal.mp4',
        'url'    => 'http://distribution.bbb3d.renderfarming.net/video/mp4/bbb_sunflower_1080p_30fps_normal.mp4',
        'length' => 634,
        'sprite' => ['width' => 960, 'height' => 544]
    ];

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->outputFS = new Filesystem(new Local(dirname($this->testData['src'])));

        // download test video if it's not available yet
        try {
            if (!$this->outputFS->has(basename($this->testData['src']))) {
                $client   = new Client();
                $response = $client->get($this->testData['url'], ['stream' => true]);
                $body     = $response->getBody();
                $fh       = fopen($this->testData['src'], 'wb');

                while (!$body->eof()) {
                    fwrite($fh, $body->read(4096));
                }

                fclose($fh);
            }

            $this->outputFS->assertPresent(basename($this->testData['src']));
        } catch (\Exception $e) {
            $this->markTestSkipped('Failed downloading sample video file');
        }
    }


    /**
     * @param $file
     * @return Image
     */
    protected function image($file): Image
    {
        return ImageManagerStatic::make($file);
    }


    public function testGetterSetter()
    {
        $ts = new ThumbnailSprite();

        $this->assertEquals($ts, $ts->setSource($this->testData['src']));
        $this->assertEquals($this->testData['src'], $ts->getSource());

        $this->assertEquals($ts, $ts->setWidth(1000));
        $this->assertEquals(1000, $ts->getWidth());

        $this->assertEquals($ts, $ts->setMinThumbs(10));
        $this->assertEquals(10, $ts->getMinThumbs());

        $this->assertEquals($ts, $ts->setOutputDirectory(dirname($this->testData['src'])));
        $this->assertEquals(dirname($this->testData['src']), $ts->getOutputDirectory());

        $this->assertEquals($ts, $ts->setOutputImageDirectory(dirname($this->testData['src'])));
        $this->assertEquals(dirname($this->testData['src']), $ts->getOutputImageDirectory());

        $this->assertEquals($ts, $ts->setPrefix('gettersetter'));
        $this->assertEquals('gettersetter', $ts->getPrefix());

        $this->assertEquals($ts, $ts->setUrlPrefix('http://example.org'));
        $this->assertEquals('http://example.org', $ts->getUrlPrefix());

        $this->assertEquals($ts, $ts->setRate(42));
        $this->assertEquals(42, $ts->getRate());

        $this->assertInstanceOf(ThumbnailerInterface::class, $ts->getThumbnailer());

        $thumbnailer = new Ffmpeg();
        $this->assertEquals($ts, $ts->setThumbnailer($thumbnailer));
        $this->assertEquals($thumbnailer, $ts->getThumbnailer());
    }

    /**
     * Simple sprite and vtt generation with default values
     */
    public function testSpriteGeneration()
    {
        $ts  = new ThumbnailSprite();
        $ret = $ts->setSource($this->testData['src'])
                  ->setOutputDirectory(dirname($this->testData['src']))
                  ->generate();

        $this->assertArrayHasKey('vttFile', $ret);
        $this->assertArrayHasKey('sprite', $ret);

        $this->assertFileExists($ret['vttFile']);
        $this->assertFileExists($ret['sprite']);

        // check output sprite image
        $img = $this->image($ret['sprite']);
        $this->assertEquals($this->testData['sprite']['width'], $img->getWidth());
        $this->assertEquals($this->testData['sprite']['height'], $img->getHeight());
    }

    /**
     * Sprite and vtt generation with ffmpegthumbnailer
     */
    public function testFFMPEGThumbnailer()
    {
        $ts  = new ThumbnailSprite();
        $ret = $ts->setSource($this->testData['src'])
                  ->setOutputDirectory(dirname($this->testData['src']))
                  ->setPrefix('ffmpegthumbnailer')
                  ->setThumbnailer(new FfmpegThumbnailer())
                  ->generate();

        $this->assertArrayHasKey('vttFile', $ret);
        $this->assertArrayHasKey('sprite', $ret);

        $this->assertFileExists($ret['vttFile']);
        $this->assertFileExists($ret['sprite']);

        // check output sprite image
        $img = $this->image($ret['sprite']);
        $this->assertEquals($this->testData['sprite']['width'], $img->getWidth());
        $this->assertEquals($this->testData['sprite']['height'], $img->getHeight());
    }

    /**
     * Test setting prefixes for both output filename and URLs in vtt file
     */
    public function testPrefixes()
    {
        $ts  = new ThumbnailSprite();
        $ret = $ts->setSource($this->testData['src'])
                  ->setOutputDirectory(dirname($this->testData['src']))
                  ->setPrefix('blubber')
                  ->setUrlPrefix('http://example.org')
                  ->generate();

        $this->assertArrayHasKey('vttFile', $ret);
        $this->assertArrayHasKey('sprite', $ret);

        $this->assertFileExists($ret['vttFile']);
        $this->assertFileExists($ret['sprite']);

        $this->assertEquals(dirname($this->testData['src']) . '/blubber.vtt', $ret['vttFile']);
        $this->assertEquals(dirname($this->testData['src']) . '/blubber.jpg', $ret['sprite']);

        $vtt = $this->outputFS->read('blubber.vtt');

        $this->assertContains('http://example.org/blubber.jpg#xywh', $vtt);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUnknownSource()
    {
        $ts = new ThumbnailSprite();
        $ts->setSource('unknown-file-42');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidRate()
    {
        $ts = new ThumbnailSprite();
        $ts->setRate(0);
    }

    /**
     * Cleanup output files after each test
     */
    public function tearDown()
    {
        $cleanup = [
            'sprite.jpg',
            'sprite.vtt',
            'blubber.jpg',
            'blubber.vtt',
            'ffmpegthumbnailer.jpg',
            'ffmpegthumbnailer.vtt'
        ];

        foreach ($cleanup as $file) {
            $this->outputFS->has($file) && $this->outputFS->delete($file);
        }
    }

}
