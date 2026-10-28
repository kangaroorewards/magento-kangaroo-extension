<?php
/**
 * Collects the SKUs touched during a Magento request and notifies the integration API once per SKU.
 *
 * - Observers only call add(): this is an in-memory operation, nothing is sent while Magento is
 *   still saving (so the API never reads a half-committed state and saves are never slowed down).
 * - flush() runs when the PHP request ends, after the response has been released to the client
 *   (when PHP-FPM allows it).
 * - Notifications for the same store + SKU + state are sent once per short window, even if several
 *   observers or requests report the same change.
 * - Retryable failures are retried once; failures are logged and never interrupt Magento saves.
 * - Nothing in here ever throws into Magento.
 */
namespace Kangaroorewards\Core\Model\ProductState;

use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

class Dispatcher
{
    /** Seconds during which an identical notification is considered a duplicate. */
    const DEDUP_WINDOW = 10;

    /** Total delivery attempts for retryable failures (first send included). */
    const MAX_ATTEMPTS = 2;

    const CACHE_PREFIX = 'kangaroo_product_state_';

    /**
     * @var ProductStateClientInterface
     */
    private $client;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $deferToShutdown;

    /**
     * @var array
     */
    private $pending = [];

    /**
     * @var bool|null
     */
    private $enabled = null;

    /**
     * @var bool
     */
    private $shutdownRegistered = false;

    /**
     * @param ProductStateClientInterface $client
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param bool $deferToShutdown Flush automatically when the PHP process ends
     */
    public function __construct(
        ProductStateClientInterface $client,
        CacheInterface $cache,
        LoggerInterface $logger,
        $deferToShutdown = true
    ) {
        $this->client = $client;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->deferToShutdown = (bool)$deferToShutdown;
    }

    /**
     * Whether events should be collected at all (the store is connected to Kangaroo).
     *
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->enabled === null) {
            try {
                $this->enabled = (bool)$this->client->isConnected();
                if (!$this->enabled) {
                    $this->logger->info('[Kangaroo Rewards] Product state notifications disabled: no integration credentials');
                }
            } catch (\Throwable $exception) {
                $this->logger->warning('[Kangaroo Rewards] Product state: connection check failed', [
                    'error' => $exception->getMessage(),
                ]);
                $this->enabled = false;
            }
        }

        return $this->enabled;
    }

    /**
     * Register a changed SKU. Repeated calls for the same store + SKU are merged.
     *
     * @param int|string $storeId
     * @param string $domain
     * @param string $sku
     * @param array $state Optional change fingerprint: price, special_price, qty, is_in_stock
     * @param string $source Observer or plugin that detected the change
     * @return void
     */
    public function add($storeId, $domain, $sku, array $state = [], $source = 'unknown')
    {
        $sku = trim((string)$sku);
        $storeId = (int)$storeId;
        if ($sku === '' || $storeId <= 0 || trim((string)$domain) === '') {
            return;
        }

        $key = $storeId . '|' . $sku;
        if (!isset($this->pending[$key])) {
            $this->pending[$key] = [
                'store_id' => $storeId,
                'domain' => (string)$domain,
                'sku' => $sku,
                'state' => [],
                'occurred_at' => gmdate('Y-m-d\TH:i:s\Z', $this->now()),
            ];
        }
        $merged = array_merge($this->pending[$key]['state'], $this->normalizeState($state));
        ksort($merged);
        $this->pending[$key]['state'] = $merged;

        $this->logger->info('[Kangaroo Rewards] Product state change detected', [
            'source' => (string)$source,
            'store_id' => $storeId,
            'sku' => $sku,
        ]);
        $this->scheduleFlush();
    }

    /**
     * Deliver everything collected so far.
     *
     * @return void
     */
    public function flush()
    {
        if (!$this->pending) {
            return;
        }

        $entries = array_values($this->pending);
        $this->pending = [];

        if (!$this->isEnabled()) {
            return;
        }

        $this->releaseClientConnection();

        foreach ($entries as $entry) {
            try {
                $cacheId = $this->dedupCacheId($entry);
                if (!$this->claim($cacheId)) {
                    $this->logger->info('[Kangaroo Rewards] Product state duplicate suppressed', [
                        'store_id' => $entry['store_id'],
                        'sku' => $entry['sku'],
                    ]);
                    continue;
                }

                $this->deliver($entry, $cacheId);
            } catch (\Throwable $exception) {
                $this->logger->error('[Kangaroo Rewards] Product state dispatch failed', [
                    'store_id' => $entry['store_id'],
                    'sku' => $entry['sku'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return int
     */
    protected function now()
    {
        return time();
    }

    /**
     * Send the response to the browser first (PHP-FPM) so the user never waits for the API.
     *
     * @return void
     */
    protected function releaseClientConnection()
    {
        if ($this->deferToShutdown && PHP_SAPI !== 'cli' && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * @return void
     */
    private function scheduleFlush()
    {
        if ($this->deferToShutdown && !$this->shutdownRegistered) {
            $this->shutdownRegistered = true;
            register_shutdown_function([$this, 'flush']);
        }
    }

    /**
     * @param array $entry
     * @param string $cacheId
     * @return void
     */
    private function deliver(array $entry, $cacheId)
    {
        $this->logger->info('[Kangaroo Rewards] Sending product-state event', [
            'store_id' => $entry['store_id'],
            'sku' => $entry['sku'],
            'occurred_at' => $entry['occurred_at'],
            'attempt' => 1,
        ]);
        $result = $this->client->send($entry['domain'], $entry['store_id'], $entry['sku'], $entry['occurred_at']);
        $attempts = 1;

        if (!$result['success'] && $result['retryable'] && $attempts < self::MAX_ATTEMPTS) {
            $attempts++;
            $this->logger->info('[Kangaroo Rewards] Retrying product-state event', [
                'store_id' => $entry['store_id'],
                'sku' => $entry['sku'],
                'attempt' => $attempts,
            ]);
            $result = $this->client->send($entry['domain'], $entry['store_id'], $entry['sku'], $entry['occurred_at']);
        }

        if ($result['success']) {
            $this->logger->info('[Kangaroo Rewards] Product state sent', [
                'store_id' => $entry['store_id'],
                'sku' => $entry['sku'],
                'attempts' => $attempts,
            ]);
            return;
        }

        $this->cache->remove($cacheId);
        $this->logger->error('[Kangaroo Rewards] Product state delivery failed', [
            'store_id' => $entry['store_id'],
            'sku' => $entry['sku'],
            'http_code' => $result['http_code'],
            'attempts' => $attempts,
            'error' => $result['error'],
        ]);
    }

    /**
     * Atomically-enough reserve the dedup slot: false when it is already taken.
     *
     * @param string $cacheId
     * @return bool
     */
    private function claim($cacheId)
    {
        if ($this->cache->load($cacheId) !== false) {
            return false;
        }
        $this->cache->save('1', $cacheId, [], self::DEDUP_WINDOW);

        return true;
    }

    /**
     * Key: store ID + SKU + state fingerprint, valid for the dedup window.
     *
     * @param array $entry
     * @return string
     */
    private function dedupCacheId(array $entry)
    {
        return self::CACHE_PREFIX . sha1($entry['store_id'] . '|' . $entry['sku'] . '|' . json_encode($entry['state']));
    }

    /**
     * @param array $state
     * @return array
     */
    private function normalizeState(array $state)
    {
        $normalized = [];
        foreach (['price', 'special_price', 'qty', 'is_in_stock'] as $field) {
            if (!isset($state[$field]) || $state[$field] === '') {
                continue;
            }
            $value = $state[$field];
            $normalized[$field] = is_numeric($value) ? (string)(float)$value : (string)$value;
        }
        ksort($normalized);

        return $normalized;
    }
}
