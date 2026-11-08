<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // The store is in the Philippines, so "today" means Manila time,
        // whatever the computer's or the server's PHP settings say.
        date_default_timezone_set('Asia/Manila');

        parent::boot();
    }
}