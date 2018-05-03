<?php

namespace FireflyIII\Import\JobConfiguration;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use FireflyIII\Support\Import\Configuration\File\Initial;
use FireflyIII\Support\Import\Configuration\File\Map;
use FireflyIII\Support\Import\Configuration\File\Roles;
use FireflyIII\Support\Import\Configuration\File\UploadConfig;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class FileJobConfiguration
 *
 * @package FireflyIII\Import\JobConfiguration
 */
class FileJobConfiguration implements JobConfigurationInterface
{
    /** @var ImportJob */
    private $job;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * ConfiguratorInterface constructor.
     */
    public function __construct()
    {
        $this->repository = app(ImportJobRepositoryInterface::class);
    }

    /**
     * Store any data from the $data array into the job. Anything in the message bag will be flashed
     * as an error to the user, regardless of its content.
     *
     * @param array $data
     *
     * @return MessageBag
     * @throws FireflyException
     */
    public function configureJob(array $data): MessageBag
    {
        /** @var ConfigurationInterface $object */
        $object = app($this->getConfigurationClass());
        $object->setJob($this->job);
        $result        = $object->storeConfiguration($data);

        return $result;
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        /** @var ConfigurationInterface $object */
        $object = app($this->getConfigurationClass());
        $object->setJob($this->job);

        return $object->getData();
    }

    /**
     * Returns the view of the next step in the job configuration.
     *
     * @return string
     * @throws FireflyException
     */
    public function getNextView(): string
    {
        switch ($this->job->stage) {
            case 'new': // has nothing, no file upload or anything.
                return 'import.file.new';
            case 'upload-config': // has file, needs file config.
                return 'import.file.upload-config';
            case 'roles': // has configured file, needs roles.
                return 'import.file.roles';
            case 'map': // has roles, needs mapping.
                return 'import.file.map';
        }
        throw new FireflyException(sprintf('No view for stage "%s"', $this->job->stage));
    }

    /**
     * Returns true when the initial configuration for this job is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        if ('ready' === $this->job->stage) {
            Log::debug('isJobConfigured returns true');

            return true;
        }
        Log::debug('isJobConfigured returns false');

        return false;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        $this->job = $job;
        $this->repository->setUser($job->user);
    }


    /**
     * @return string
     *
     * @throws FireflyException
     */
    private function getConfigurationClass(): string
    {
        $class = false;
        Log::debug(sprintf('Now in getConfigurationClass() for stage "%s"', $this->job->stage));

        switch ($this->job->stage) {
            case 'new': // has nothing, no file upload or anything.
                $class = Initial::class;
                break;
            case 'upload-config': // has file, needs file config.
                $class = UploadConfig::class;
                break;
            case 'roles': // has configured file, needs roles.
                $class = Roles::class;
                break;
            case 'map': // has roles, needs mapping.
                $class = Map::class;
                break;
            default:
                break;
        }

        if (false === $class || 0 === \strlen($class)) {
            throw new FireflyException(sprintf('Cannot handle job stage "%s" in getConfigurationClass().', $this->job->stage));
        }
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Class %s does not exist in getConfigurationClass().', $class)); // @codeCoverageIgnore
        }
        Log::debug(sprintf('Configuration class is "%s"', $class));

        return $class;
    }
}