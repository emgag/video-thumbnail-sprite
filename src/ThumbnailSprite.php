<?php
declare(strict_types=1);

namespace Emgag\Video\ThumbnailSprite;

use Captioning\Format\WebvttCue;
use Captioning\Format\WebvttFile;
use DateTime;
use Emgag\Flysystem\Tempdir;
use Emgag\Video\ThumbnailSprite\Thumbnailer\Ffmpeg;
use Emgag\Video\ThumbnailSprite\Thumbnailer\ThumbnailerInterface;
use FFMpeg\FFProbe;
use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\Plugin\ListFiles;
use Symfony\Component\Process\Process;

/**
 * Generate video thumbnail sprite and WebVTT file to be used in JWPlayer
 */
class ThumbnailSprite
{

    /**
     * @var string
     */
    private $source = '';

    /**
     * @var string
     */
    private $outputDirectory = '';

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
     * @var Thumbnailer\ThumbnailerInterface
     */
    private $thumbnailer;

    /**
     * @var string
     */
    private $outputImageDirectory = '';

    /**
     * ThumbnailSprite constructor.
     */
    public function __construct()
    {
        $this->thumbnailer = new Ffmpeg();
    }

    /**
     * @return ThumbnailerInterface
     */
    public function getThumbnailer(): ThumbnailerInterface
    {
        return $this->thumbnailer;
    }

    /**
     * @param ThumbnailerInterface $thumbnailer
     * @return $this
     */
    public function setThumbnailer(ThumbnailerInterface $thumbnailer): ThumbnailSprite
    {
        $this->thumbnailer = $thumbnailer;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix(string $prefix): ThumbnailSprite
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return int
     */
    public function getRate(): int
    {
        return $this->rate;
    }

    /**
     * @param int $rate
     * @return $this
     */
    public function setRate(int $rate): ThumbnailSprite
    {
        if ($rate === 0) {
            throw new \InvalidArgumentException('Rate must be greater than 0');
        }

        $this->rate = $rate;

        return $this;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     * @return $this
     */
    public function setWidth(int $width): ThumbnailSprite
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return $this
     * @throws \RuntimeException
     */
    public function setSource(string $source): ThumbnailSprite
    {
        if (!file_exists($source)) {
            throw new \RuntimeException(sprintf("Source video file %s not found", $source));
        }

        $this->source = $source;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputDirectory(): string
    {
        return $this->outputDirectory;
    }

    /**
     * @param string $outputDirectory
     * @return $this
     */
    public function setOutputDirectory(string $outputDirectory): ThumbnailSprite
    {
        $this->outputDirectory = $outputDirectory;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }

    /**
     * @param string $urlPrefix
     * @return $this
     */
    public function setUrlPrefix(string $urlPrefix): ThumbnailSprite
    {
        $this->urlPrefix = $urlPrefix;

        return $this;
    }

    /**
     * @return int
     */
    public function getMinThumbs(): int
    {
        return $this->minThumbs;
    }

    /**
     * @param int $minThumbs
     * @return $this
     */
    public function setMinThumbs(int $minThumbs): ThumbnailSprite
    {
        $this->minThumbs = $minThumbs;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputImageDirectory(): string
    {
        return $this->outputDirectory;
    }

    /**
     * @param string $outputImageDirectory
     * @return $this
     */
    public function setOutputImageDirectory(string $outputImageDirectory): ThumbnailSprite
    {
        $this->outputImageDirectory = $outputImageDirectory;

        return $this;
    }

    /**
     * Generates sprite and WebVTT for selected video
     *
     * @throws \Exception
     * @return string[]
     */
    public function generate(): array
    {
        // create temporay directory
        $tempDir = new Tempdir('sprite');
        $tempDir->addPlugin(new ListFiles);

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
                $this->setRate((int)floor($duration / $this->getMinThumbs()));
            }
        }

        $this->thumbnailer
            ->setSource($this->getSource())
            ->setWidth($this->getWidth())
            ->setDestination($tempDir->getPath());

        // capture images to tempdir
        for ($i = 0; $i <= $duration; $i += $this->getRate()) {
            $this->thumbnailer->run($i, (int)floor($i / $this->getRate()));
        }

        if (!empty($this->outputImageDirectory)) {
            foreach ($tempDir->listFiles() as $image) {
                copy($tempDir->getPath() . $image['path'], $this->outputImageDirectory . $image['basename']);
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
            $spriteFile
        );

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

        $vtt      = new WebvttFile();
        $numFiles = count($tempDir->listFiles());

        for ($i = 0; $i < $numFiles; $i++) {
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

        return [
            'vttFile' => $vttFile,
            'sprite'  => $spriteFile,
        ];
    }

    /**
     * Converts seconds to CUE time format HH:MM:SS.000
     *
     * @param $seconds
     * @return string
     */
    private function secondsToCue($seconds)
    {
        return (new DateTime("@0"))
            ->diff(new DateTime("@$seconds"))
            ->format('%H:%I:%S.000');
    }

}
