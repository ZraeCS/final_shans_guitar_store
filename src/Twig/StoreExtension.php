<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Links from the admin panel to the store website.
 *
 *     {{ store_url('images/products/Martin_D28.jpg') }}
 *     -> http://localhost/shans_guitar/images/products/Martin_D28.jpg
 *
 * Guitar photos live in the store's folder, so the admin shows them from there.
 */
final class StoreExtension extends AbstractExtension
{
    public function __construct(
        #[Autowire(env: 'STORE_BASE_URL')]
        private readonly string $storeBaseUrl,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('store_url', $this->storeUrl(...)),
        ];
    }

    public function storeUrl(string $path = ''): string
    {
        if (preg_match('#^https?://#', $path)) {
            return $path; // already a full address
        }

        return rtrim($this->storeBaseUrl, '/').'/'.ltrim($path, '/');
    }
}
