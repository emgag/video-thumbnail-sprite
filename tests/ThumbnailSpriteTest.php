<?php

use Emgag\Video\ThumbnailSprite\ThumbnailSprite;
use GuzzleHttp\Client;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class ThumbnailSpriteTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Filesystem
     */
    public $outputFS;
    public $testSrc = __DIR__ . '/test_data/bbb_sunflower_1080p_30fps_normal.mp4';
    public $testSrcUrl = 'https://video.labs.gameswelt.de/big-bucks-bunny/bbb_sunflower_1080p_30fps_normal.mp4';

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
                $fh       = fopen($this->testSrc, 'w');

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
        $ts = new ThumbnailSprite();
        $ts->setSource($this->testSrc);
        $ts->setOutputDirectory(dirname($this->testSrc));
        $ts->generate();

        $this->assertTrue($this->outputFS->has('sprite.jpg'));
        $this->assertTrue($this->outputFS->has('sprite.vtt'));
    }

    /**
     * Test setting prefixes for both output filename and URLs in vtt file
     */
    public function testPrefixes()
    {
        $ts = new ThumbnailSprite();
        $ts->setSource($this->testSrc);
        $ts->setOutputDirectory(dirname($this->testSrc));
        $ts->setPrefix('blubber');
        $ts->setUrlPrefix('http://example.org');
        $ts->generate();

        $this->assertTrue($this->outputFS->has('blubber.jpg'));
        $this->assertTrue($this->outputFS->has('blubber.vtt'));

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
        $cleanup = ['sprite.jpg', 'sprite.vtt', 'blubber.jpg', 'blubber.vtt'];
        foreach ($cleanup as $file) {
            $this->outputFS->has($file) && $this->outputFS->delete($file);
        }
    }

}
