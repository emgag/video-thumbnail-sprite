<?php

use Emgag\Video\ThumbnailSprite\Thumbnailer\FfmpegThumbnailer;
use Emgag\Video\ThumbnailSprite\ThumbnailSprite;
use GuzzleHttp\Client;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ThumbnailSpriteTest extends TestCase
{

    /**
     * @var Filesystem
     */
    public $outputFS;
    public $testSrc = __DIR__ . '/test_data/bbb_sunflower_1080p_30fps_normal.mp4';
    public $testSrcUrl = 'http://distribution.bbb3d.renderfarming.net/video/mp4/bbb_sunflower_1080p_30fps_normal.mp4';

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->outputFS = new Filesystem(new Local(dirname($this->testSrc)));

        // download test video if it's not available yet
        try {
            if (!$this->outputFS->has(basename($this->testSrc))) {
                $client   = new Client();
                $response = $client->get($this->testSrcUrl, ['stream' => true]);
                $body     = $response->getBody();
                $fh       = fopen($this->testSrc, 'wb');

                while (!$body->eof()) {
                    fwrite($fh, $body->read(4096));
                }

                fclose($fh);
            }

            $this->outputFS->assertPresent(basename($this->testSrc));
        } catch (\Exception $e) {
            $this->markTestSkipped('Failed downloading sample video file');
        }
    }

    /**
     * Simple sprite and vtt generation with default values
     */
    public function testSpriteGeneration()
    {
        $ts  = new ThumbnailSprite();
        $ret = $ts->setSource($this->testSrc)
                  ->setOutputDirectory(dirname($this->testSrc))
                  ->generate();

        $this->assertArrayHasKey('vttFile', $ret);
        $this->assertArrayHasKey('sprite', $ret);

        $this->assertTrue($this->outputFS->has(basename($ret['vttFile'])));
        $this->assertTrue($this->outputFS->has(basename($ret['sprite'])));
    }

    /**
     * Sprite and vtt generation with ffmpegthumbnailer
     */
    public function testFFMPEGThumbnailer()
    {
        $ts  = new ThumbnailSprite();
        $ret = $ts->setSource($this->testSrc)
                  ->setOutputDirectory(dirname($this->testSrc))
                  ->setPrefix('ffmpegthumbnailer')
                  ->setThumbnailer(new FfmpegThumbnailer())
                  ->generate();

        $this->assertArrayHasKey('vttFile', $ret);
        $this->assertArrayHasKey('sprite', $ret);

        $this->assertTrue($this->outputFS->has(basename($ret['vttFile'])));
        $this->assertTrue($this->outputFS->has(basename($ret['sprite'])));
    }

    /**
     * Test setting prefixes for both output filename and URLs in vtt file
     */
    public function testPrefixes()
    {
        $ts  = new ThumbnailSprite();
        $ret = $ts->setSource($this->testSrc)
                  ->setOutputDirectory(dirname($this->testSrc))
                  ->setPrefix('blubber')
                  ->setUrlPrefix('http://example.org')
                  ->generate();

        $this->assertArrayHasKey('vttFile', $ret);
        $this->assertArrayHasKey('sprite', $ret);

        $this->assertEquals(dirname($this->testSrc) . '/blubber.vtt', $ret['vttFile']);
        $this->assertEquals(dirname($this->testSrc) . '/blubber.jpg', $ret['sprite']);

        $this->assertTrue($this->outputFS->has(basename($ret['vttFile'])));
        $this->assertTrue($this->outputFS->has(basename($ret['sprite'])));

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
            //$this->outputFS->has($file) && $this->outputFS->delete($file);
        }
    }

}
