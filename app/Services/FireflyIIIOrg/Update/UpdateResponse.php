<?php




declare(strict_types=1);



namespace FireflyIII\Services\FireflyIIIOrg\Update;

use Carbon\Carbon;

class UpdateResponse
{
    private bool   $newVersionAvailable = false;
    private string $error               = '';
    private string $newVersion          = '1.0.0';
    private Carbon $publishedAt;

    public function getError(): string
    {
        return $this->error;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }

    public function getPublishedAt(): Carbon
    {
        return $this->publishedAt;
    }

    public function isNewVersionAvailable(): bool
    {
        return $this->newVersionAvailable;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function setNewVersion(string $newVersion): void
    {
        $this->newVersion = $newVersion;
    }

    public function setNewVersionAvailable(bool $newVersionAvailable): void
    {
        $this->newVersionAvailable = $newVersionAvailable;
    }

    public function setPublishedAt(Carbon $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }
}
