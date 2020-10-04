<?php
declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Str;

/**
 * Class CreateFirstUser
 * @package FireflyIII\Console\Commands
 */
class CreateFirstUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:create-first-user {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new user and gives admin rights. Outputs the password on the command line. Strictly for testing.';

    private UserRepositoryInterface $repository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ('testing' !== env('APP_ENV', 'local')) {
            $this->error('This command only works in the testing environment.');
            return 1;
        }
        $this->stupidLaravel();
        $count = $this->repository->count();
        if ($count > 0) {
            $this->error('Already have more than zero users in DB.');
            return 1;
        }
        $data           = [
            'blocked'      => false,
            'blocked_code' => null,
            'email'        => $this->argument('email'),
            'role'         => 'owner',
        ];
        $password       = Str::random(24);
        $user           = $this->repository->store($data);
        $user->password = Hash::make($password);
        $user->save();
        $user->setRememberToken(Str::random(60));

        $this->info(sprintf('Created new admin user (ID #%d) with email address "%s" and password "%s".', $user->id, $user->email, $password));
        $this->error('Change this password.');
        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     *
     * @codeCoverageIgnore
     */
    private function stupidLaravel(): void
    {
        $this->repository = app(UserRepositoryInterface::class);

    }
}
