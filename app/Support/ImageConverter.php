<?php

/**
 * ImageConverter.php
 */

declare(strict_types=1);

namespace FireflyIII\Support;

use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Converts uploaded images to WebP format for storage efficiency.
 */
class ImageConverter
{
    /**
     * MIME types that should be converted to WebP.
     * webp, svg, heic, ico are intentionally excluded — they're already optimized or vector.
     */
    private const CONVERTIBLE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/tiff',
    ];

    /**
     * WebP conversion quality (0-100).
     */
    private const WEBP_QUALITY = 85;

    /**
     * Maximum image size in bytes to attempt conversion.
     * Images larger than this are stored as-is to avoid memory exhaustion.
     */
    private const MAX_CONVERSION_SIZE = 50_000_000; // 50 MB

    public function shouldConvert(string $mime, int $size): bool
    {
        if ($size > self::MAX_CONVERSION_SIZE) {
            Log::debug(sprintf('Image too large for WebP conversion (%d bytes), storing as-is.', $size));

            return false;
        }

        return in_array($mime, self::CONVERTIBLE_MIMES, true);
    }

    /**
     * Convert image content to WebP format.
     *
     * @param string $content Raw image bytes
     * @param string $currentMime Current MIME type
     *
     * @return array{0: string, 1: string, 2: int} [content, new_mime, new_size]
     */
    public function convertToWebP(string $content, string $currentMime): array
    {
        if (!$this->shouldConvert($currentMime, strlen($content))) {
            return [$content, $currentMime, strlen($content)];
        }

        try {
            $image   = Image::read($content);
            $encoded = $image->toWebp(self::WEBP_QUALITY)->toString();
            $newSize = strlen($encoded);

            Log::debug(sprintf(
                'Converted %s (%d bytes) to WebP (%d bytes).',
                $currentMime,
                strlen($content),
                $newSize
            ));

            return [$encoded, 'image/webp', $newSize];
        } catch (\Throwable $e) {
            Log::warning(sprintf(
                'WebP conversion failed for %s: %s. Storing as-is.',
                $currentMime,
                $e->getMessage()
            ));

            return [$content, $currentMime, strlen($content)];
        }
    }
}
