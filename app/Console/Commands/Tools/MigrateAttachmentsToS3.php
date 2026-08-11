<?php

/**
 * MigrateAttachmentsToS3.php
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Tools;

use FireflyIII\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Migrates existing attachment files from local storage to S3 (Mega S4).
 */
class MigrateAttachmentsToS3 extends Command
{
    protected $signature = 'firefly-iii:migrate-attachments-to-s3'
        .'{--dry-run : List what would be migrated without actually uploading}';

    protected $description = 'Migrate existing attachment files from local storage to S3 (Mega S4)';

    public function handle(): int
    {
        $attachments = Attachment::where('uploaded', true)->get();

        if (0 === $attachments->count()) {
            $this->info('No attachments found. Nothing to migrate.');

            return 0;
        }

        $this->info(sprintf('Found %d attachment(s) to process.', $attachments->count()));

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no files will be uploaded.');

            foreach ($attachments as $attachment) {
                $this->line(sprintf(
                    '  [DRY RUN] %s (%s, %d bytes)',
                    $attachment->fileName(),
                    $attachment->filename,
                    $attachment->size
                ));
            }

            return 0;
        }

        $s3Disk    = Storage::disk('upload');
        $localDisk = Storage::disk('local_upload');

        $successCount = 0;
        $skipCount    = 0;
        $failCount    = 0;

        $bar = $this->output->createProgressBar($attachments->count());
        $bar->start();

        foreach ($attachments as $attachment) {
            $fileName = $attachment->fileName();

            // Check if local file exists.
            if (!$localDisk->exists($fileName)) {
                $this->warn(sprintf(
                    "\n  Local file not found: %s (attachment #%d) — skipping.",
                    $fileName,
                    $attachment->id
                ));
                $skipCount++;
                $bar->advance();

                continue;
            }

            // Check if already on S3 — clean up local copy.
            if ($s3Disk->exists($fileName)) {
                $localDisk->delete($fileName);
                $this->line(sprintf(
                    "\n  Already on S3, deleted local: %s",
                    $fileName
                ));
                $skipCount++;
                $bar->advance();

                continue;
            }

            try {
                $content = $localDisk->get($fileName);

                if (null === $content || '' === $content) {
                    $this->warn(sprintf(
                        "\n  Empty file: %s (attachment #%d) — skipping.",
                        $fileName,
                        $attachment->id
                    ));
                    $skipCount++;
                    $bar->advance();

                    continue;
                }

                $s3Disk->put($fileName, $content, [
                    'visibility'  => 'private',
                    'ContentType' => $attachment->mime,
                ]);

                // Verify the upload.
                if (!$s3Disk->exists($fileName)) {
                    throw new \RuntimeException('Upload verification failed — file not found on S3 after put.');
                }

                $localDisk->delete($fileName);

                $successCount++;
            } catch (\Throwable $e) {
                $this->error(sprintf(
                    "\n  Failed: %s — %s",
                    $fileName,
                    $e->getMessage()
                ));
                $failCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->info(sprintf(
            'Done. Success: %d, Skipped: %d, Failed: %d.',
            $successCount,
            $skipCount,
            $failCount
        ));

        return $failCount > 0 ? 1 : 0;
    }
}
