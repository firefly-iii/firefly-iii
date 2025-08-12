<?php

declare(strict_types=1);

namespace FireflyIII\Support\Singleton;

class PreferencesSingleton
{
    private static ?PreferencesSingleton $instance = null;

    private array $preferences                     = [];

    private function __construct()
    {
        // Private constructor to prevent direct instantiation.
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function resetPreferences(): void
    {
        $this->preferences = [];
    }

    public function setPreference(string $key, mixed $value): void
    {
        $this->preferences[$key] = $value;
    }

    public function getPreference(string $key): mixed
    {
        return $this->preferences[$key] ?? null;
    }
}
