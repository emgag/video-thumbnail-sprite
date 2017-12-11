<?php
declare(strict_types=1);

namespace Emgag\Video\ThumbnailSprite\Thumbnailer;

use Symfony\Component\Process\Process;

class Ffmpeg extends Thumbnailer
{
    /**
     * @param int $offset
     * @param int $num
     * @return bool
     */
    public function run(int $offset, int $num): bool
    {
        $cmd = sprintf('ffmpeg -y -ss %d -i %s -frames:v 1 -filter:v scale=%d:-1 %s/%04d.jpg',
            $offset,
            $this->source,
            $this->width,
            $this->destination,
            $num
        );

        $proc = new Process($cmd);
        $proc->setTimeout(null);
        $proc->run();

        if (!$proc->isSuccessful()) {
            throw new \RuntimeException($cmd . ":" . $proc->getErrorOutput());
        }

        return true;
    }

}