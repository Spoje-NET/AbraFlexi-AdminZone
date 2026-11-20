<?php

declare(strict_types=1);

/**
 * AdminZone - installed external service extensions.
 *
 * @copyright  2018-2026 Spoje.Net
 */

namespace AdminZone;

/**
 * Finds the ExtIdService extensions: every class AdminZone\extid\<Name>ExtIdService
 * in the directory extid/ next to this file. Extension packages only drop their
 * class there; the core knows nothing about the services.
 */
final class ExtIdRegistry
{
    /**
     * @var null|array<string,ExtIdService>
     */
    private static ?array $services = null;

    /**
     * @return array<string,ExtIdService> key => service, ordered by title
     */
    public static function all(): array
    {
        if (self::$services === null) {
            self::$services = self::discover(__DIR__ . '/extid');
        }

        return self::$services;
    }

    public static function get(string $key): ?ExtIdService
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Forget discovered services (tests).
     */
    public static function reset(): void
    {
        self::$services = null;
    }

    /**
     * @return array<string,ExtIdService>
     */
    public static function discover(string $directory): array
    {
        $services = [];

        foreach (glob($directory . '/*ExtIdService.php') ?: [] as $file) {
            $class = __NAMESPACE__ . '\\extid\\' . basename($file, '.php');

            try {
                if (!class_exists($class) || !is_subclass_of($class, ExtIdService::class)) {
                    continue;
                }

                $service = new $class();
                $services[$service->key()] = $service;
            } catch (\Throwable $exc) {
                // extension with a missing library must not break the core
                error_log(sprintf('AdminZone: extension %s skipped: %s', $class, $exc->getMessage()));
            }
        }

        uasort($services, static fn (ExtIdService $a, ExtIdService $b): int => strcmp($a->title(), $b->title()));

        return $services;
    }

    /**
     * @return array<int,string> pricelist codes sold by the installed extensions
     */
    public static function expectedProducts(): array
    {
        return array_values(array_unique(array_merge([], ...array_values(array_map(static fn (ExtIdService $s): array => $s->expectedProducts(), self::all())))));
    }

    /**
     * @return array<int,string> pricelist codes priced per one unit of credit
     */
    public static function creditProducts(): array
    {
        return array_values(array_unique(array_merge([], ...array_values(array_map(static fn (ExtIdService $s): array => $s->creditProducts(), self::all())))));
    }
}
