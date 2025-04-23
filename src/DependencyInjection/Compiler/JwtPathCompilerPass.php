<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class JwtPathCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $privateKey = getenv('JWT_PRIVATE_KEY');
        $publicKey = getenv('JWT_PUBLIC_KEY');

        if (!$privateKey || !$publicKey) {
            throw new \RuntimeException('JWT_PRIVATE_KEY and JWT_PUBLIC_KEY env vars must be set');
        }

        $privatePath = sys_get_temp_dir() . '/jwt-private.pem';
        $publicPath = sys_get_temp_dir() . '/jwt-public.pem';

        file_put_contents($privatePath, $privateKey);
        file_put_contents($publicPath, $publicKey);

        putenv("JWT_SECRET_KEY=$privatePath");
        putenv("JWT_PUBLIC_KEY=$publicPath");
    }
}
