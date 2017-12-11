<?php
declare(strict_types=1);

namespace Emgag\Video\ThumbnailSprite\Thumbnailer;


abstract class Thumbnailer implements ThumbnailerInterface
{

    /**
     * @var string
     */
    protected $source = '';

    /**
     * @var int
     */
    protected $width = 0;

    /**
     * @var string
     */
    protected $destination = '';

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return ThumbnailerInterface
     */
    public function setSource(string $source): ThumbnailerInterface
    {
        $this->source = $source;

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
     * @return ThumbnailerInterface
     */
    public function setWidth(int $width): ThumbnailerInterface
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     * @return ThumbnailerInterface
     */
    public function setDestination(string $destination): ThumbnailerInterface
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * @param int $offset
     * @param int $num
     * @return bool
     */
    abstract public function run(int $offset, int $num): bool;

}