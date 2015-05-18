<?php

namespace Emgag\Video;

use Captioning\Format\WebvttCue;
use Captioning\Format\WebvttFile;
use Emgag\Flysystem\Tempdir;
use FFMpeg\FFProbe;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\Process\Process;

/**
 * Generate video thumbnail sprite and WebVTT file to be used in JWPlayer
 */
class ThumbnailSprite
{

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $outputDirectory;

    /**
     * @var string
     */
    private $prefix = 'sprite';

    /**
     * @var int
     */
    private $width = 120;

    /**
     * @var int
     */
    private $rate = 10;

    /**
     * @var int
     */
    private $minThumbs = 25;

    /**
     * @var string
     */
    private $urlPrefix = '';

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return int
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param int $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @throws \notfoundException
     */
    public function setSource($source)
    {
        if (!file_exists($source)) {
            throw new \notfoundException(sprintf("source video file %s not found", $source));
        }

        $this->source = $source;
    }

    /**
     * @return mixed
     */
    public function getOutputDirectory()
    {
        return $this->outputDirectory;
    }

    /**
     * @param mixed $outputDirectory
     */
    public function setOutputDirectory($outputDirectory)
    {
        $this->outputDirectory = $outputDirectory;
    }

    /**
     * @return string
     */
    public function getUrlPrefix()
    {
        return $this->urlPrefix;
    }

    /**
     * @param string $urlPrefix
     */
    public function setUrlPrefix($urlPrefix)
    {
        $this->urlPrefix = $urlPrefix;
    }

    /**
     * @return mixed
     */
    public function getMinThumbs()
    {
        return $this->minThumbs;
    }

    /**
     * @param mixed $minThumbs
     */
    public function setMinThumbs($minThumbs)
    {
        $this->minThumbs = $minThumbs;
    }

    /**
     * Generates sprite and WebVTT for selected video
     *
     * @throws \Exception
     */
    public function generate()
    {
        // create temporay directory
        $tempDir = new TempDir('sprite');

        // get basic info about video
        $ffprobe  = FFProbe::create()->format($this->getSource());
        $duration = floatval($ffprobe->get('duration'));

        // check if sample rate is high enough to reach desired minimum amount of thumbnails
        if ($duration <= $this->getMinThumbs()) {
            // we only have a 1 second resolution, so we can't get lower than 1 obviously
            $this->setRate(1);
        } else {
            if ($duration / $this->getRate() < $this->getMinThumbs()) {
                // sample rate too high, let's adjust rate a little
                $this->setRate(floor($duration / $this->getMinThumbs()));
            }
        }

        // capture images to tempdir
        for ($i = 0; $i <= $duration; $i += $this->getRate()) {
            $cmd = sprintf('ffmpeg -y -ss %d -i %s -frames:v 1 -filter:v scale=%d:-1 %s/%04d.jpg',
                $i,
                $this->getSource(),
                $this->getWidth(),
                $tempDir->getPath(),
                floor($i / $this->getRate())
            );

            $proc = new Process($cmd);
            $proc->setTimeout(null);
            $proc->run();

            if (!$proc->isSuccessful()) {
                throw new \RuntimeException($cmd . ":" . $proc->getErrorOutput());
            }
        }

        // combine all images to one sprite with quadratic tiling
        $gridSize   = ceil(sqrt(count($tempDir->listFiles())));
        $firstImage = Image::make(sprintf('%s/0001.jpg', $tempDir->getPath()));
        $spriteFile = sprintf('%s/%s.jpg',
            $this->getOutputDirectory(),
            $this->getPrefix()
        );

        $spriteUrl = empty($this->getUrlPrefix())
            ? basename($spriteFile)
            : sprintf('%s/%s', $this->getUrlPrefix(), basename($spriteFile));

        $cmd = sprintf('montage %1$s/*.jpg -tile %2$dx -geometry %3$dx%4$d+0+0 %5$s',
            $tempDir->getPath(),
            $gridSize,
            $firstImage->width(),
            $firstImage->height(),
            $spriteFile);

        $proc = new Process($cmd);
        $proc->run();

        if (!$proc->isSuccessful()) {
            throw new \RuntimeException($proc->getErrorOutput());
        }

        // create WebVTT output
        $vttFile = sprintf('%s/%s.vtt',
            $this->getOutputDirectory(),
            $this->getPrefix()
        );

        $vtt = new WebvttFile();

        for ($i = 0; $i < count($tempDir->listFiles()); $i++) {
            $start = $i * $this->getRate();
            $end   = ($i + 1) * $this->getRate();
            $x     = ($i % $gridSize) * $firstImage->width();
            $y     = floor($i / $gridSize) * $firstImage->height();

            $vtt->addCue(new WebvttCue(
                    $this->secondsToCue($start),
                    $this->secondsToCue($end),
                    sprintf('%s#xywh=%d,%d,%d,%d',
                        $spriteUrl,
                        $x,
                        $y,
                        $firstImage->width(),
                        $firstImage->height()
                    )
                )
            );
        }

        $vtt->build();
        $vtt->save($vttFile);
    }

    /**
     * Converts seconds to CUE time format HH:MM:SS.000
     *
     * @param $seconds
     * @return string
     */
    private function secondsToCue($seconds)
    {
        return (new \DateTime("@0"))
            ->diff(new \DateTime("@$seconds"))
            ->format('%H:%I:%S.000');
    }

}