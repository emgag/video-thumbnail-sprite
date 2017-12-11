<?php
declare(strict_types=1);

namespace Emgag\Video\ThumbnailSprite\Thumbnailer;


interface ThumbnailerInterface
{

    /**
     * @return string
     */
    public function getSource(): string;

    /**
     * @param string $source
     * @return ThumbnailerInterface
     */
    public function setSource(string $source): ThumbnailerInterface;

    /**
     * @return int
     */
    public function getWidth(): int;

    /**
     * @param int $width
     * @return ThumbnailerInterface
     */
    public function setWidth(int $width): ThumbnailerInterface;

    /**
     * @return string
     */
    public function getDestination(): string;

    /**
     * @param string $destination
     * @return ThumbnailerInterface
     */
    public function setDestination(string $destination): ThumbnailerInterface;

    /**
     * Run command
     *
     * @param int $offset
     * @param int $num
     * @return bool
     */
    public function run(int $offset, int $num): bool;

}