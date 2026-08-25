<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductPreviewService
{
    /** @return array<string, mixed> */
    public function fetch(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            throw ValidationException::withMessages(['product_url' => 'Invalid product URL.']);
        }

        $addresses = gethostbynamel($host) ?: [];
        if ($addresses === [] || collect($addresses)->contains(fn (string $ip): bool => ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
            throw ValidationException::withMessages(['product_url' => 'The product URL cannot point to a private network.']);
        }

        $response = Http::connectTimeout(3)->timeout(8)->withHeaders([
            'User-Agent' => 'WiseBuy Preview Bot/1.0',
            'Accept' => 'text/html,application/xhtml+xml',
        ])->withOptions(['allow_redirects' => false])->get($url);

        if (! $response->successful() || ! Str::contains((string) $response->header('Content-Type'), ['text/html', 'application/xhtml+xml'])) {
            return [];
        }

        $html = Str::limit($response->body(), 1_000_000, '');

        return [
            'preview_title' => $this->meta($html, 'og:title') ?? $this->title($html),
            'preview_description' => $this->meta($html, 'og:description') ?? $this->meta($html, 'description', 'name'),
            'preview_image_url' => $this->meta($html, 'og:image'),
            'preview_site_name' => $this->meta($html, 'og:site_name') ?? $host,
            'preview_fetched_at' => now(),
        ];
    }

    private function meta(string $html, string $key, string $attribute = 'property'): ?string
    {
        $quotedKey = preg_quote($key, '/');
        foreach ([
            '/<meta[^>]*'.$attribute.'=["\']'.$quotedKey.'["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]*content=["\']([^"\']+)["\'][^>]*'.$attribute.'=["\']'.$quotedKey.'["\'][^>]*>/i',
        ] as $pattern) {
            if (preg_match($pattern, $html, $matches) === 1) {
                return Str::limit(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), 1000);
            }
        }

        return null;
    }

    private function title(string $html): ?string
    {
        return preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches) === 1
            ? Str::limit(trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5)), 255)
            : null;
    }
}
