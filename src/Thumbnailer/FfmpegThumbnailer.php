<?php
declare(strict_types=1);

namespace Emgag\Video\ThumbnailSprite\Thumbnailer;

use DateTime;
use Symfony\Component\Process\Process;

class FfmpegThumbnailer extends Thumbnailer
{
    /**
     * @param int $offset
     * @param int $num
     * @return bool
     */
    public function run(int $offset, int $num): bool
    {
        $cmd = sprintf('ffmpegthumbnailer -t %s -i %s -s %d -o %s/%04d.jpg',
            (new DateTime("@0"))->diff(new DateTime("@$offset"))->format('%H:%I:%S'),
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