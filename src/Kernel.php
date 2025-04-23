<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // Write JWT keys from ENV contents into temp files
        $this->initializeJwtKeys();
    }

    private function initializeJwtKeys(): void
    {
        $privateKeyContent = getenv('JWT_PRIVATE_KEY');
        $publicKeyContent = getenv('JWT_PUBLIC_KEY');

        if (!$privateKeyContent || !$publicKeyContent) {
            throw new \RuntimeException('JWT_PRIVATE_KEY and JWT_PUBLIC_KEY env variables must be set.');
        }

        $privateKeyPath = sys_get_temp_dir() . '/jwt-private.pem';
        $publicKeyPath = sys_get_temp_dir() . '/jwt-public.pem';

        // Write only if the files don't exist (optional)
        if (!file_exists($privateKeyPath)) {
            file_put_contents($privateKeyPath, $privateKeyContent);
        }

        if (!file_exists($publicKeyPath)) {
            file_put_contents($publicKeyPath, $publicKeyContent);
        }

        // Set env vars dynamically for Lexik to use
        putenv("JWT_SECRET_KEY=$privateKeyPath");
        putenv("JWT_PUBLIC_KEY=$publicKeyPath");
    }
}
