<?php

namespace App\Helpers;

/**
 * Cache Helper
 *
 * Provides utility methods for caching operations
 */
class CacheHelper
{
    /**
     * Get from cache or execute callback and store result
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute on cache miss
     * @param int $ttl Time to live in seconds
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 3600)
    {
        $cache = \Config\Services::cache();

        // Try to get from cache
        $cached = $cache->get($key);

        if ($cached !== null) {
            log_message('debug', "Cache HIT: {$key}");
            return $cached;
        }

        log_message('debug', "Cache MISS: {$key}");

        // Execute callback and cache result
        $result = $callback();

        if ($result !== null) {
            $cache->save($key, $result, $ttl);
            log_message('debug', "Cached: {$key} (TTL: {$ttl}s)");
        }

        return $result;
    }

    /**
     * Invalidate cache by key or pattern
     *
     * @param string|array $keys Single key or array of keys
     * @return bool
     */
    public static function forget($keys): bool
    {
        $cache = \Config\Services::cache();

        if (is_array($keys)) {
            foreach ($keys as $key) {
                $cache->delete($key);
                log_message('debug', "Cache invalidated: {$key}");
            }
            return true;
        }

        $result = $cache->delete($keys);
        if ($result) {
            log_message('debug', "Cache invalidated: {$keys}");
        }

        return $result;
    }

    /**
     * Invalidate all content-related caches
     *
     * @param int $contentId
     * @return void
     */
    public static function invalidateContent(int $contentId): void
    {
        $cache = \Config\Services::cache();

        // Get short code for this content
        $shortUrlModel = new \App\Models\ShortUrlModel();
        $shortUrl = $shortUrlModel->where('content_id', $contentId)->first();

        $keysToInvalidate = [
            "content_list", // Lista contenuti
            "content_data_{$shortUrl['short_code']}" ?? null, // Dati specifici contenuto
        ];

        // Invalida anche tutte le varianti HTML per tutte le lingue
        $languages = ['it', 'en', 'fr', 'de', 'sv', 'deaf'];
        foreach ($languages as $lang) {
            $keysToInvalidate[] = "html_content_{$contentId}_{$lang}";
        }

        self::forget(array_filter($keysToInvalidate));

        log_message('info', "All caches invalidated for content ID: {$contentId}");
    }

    /**
     * Clear all application caches
     *
     * @return bool
     */
    public static function flush(): bool
    {
        $cache = \Config\Services::cache();
        $result = $cache->clean();

        if ($result) {
            log_message('info', "All caches flushed");
        }

        return $result;
    }
}
