<?php


namespace FireflyIII\Api\V1\Controllers\Models\TransactionCurrency;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private CurrencyRepositoryInterface $repository;
    private UserRepositoryInterface     $userRepository;

    /**
     * CurrencyRepository constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var CurrencyRepositoryInterface repository */
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param TransactionCurrency $currency
     *
     * @return JsonResponse
     * @throws FireflyException
     * @codeCoverageIgnore
     */
    public function destroy(TransactionCurrency $currency): JsonResponse
    {
        /** @var User $admin */
        $admin = auth()->user();

        if (!$this->userRepository->hasRole($admin, 'owner')) {
            // access denied:
            throw new FireflyException('200005: You need the "owner" role to do this.'); // @codeCoverageIgnore
        }
        if ($this->repository->currencyInUse($currency)) {
            throw new FireflyException('200006: Currency in use.'); // @codeCoverageIgnore
        }
        if ($this->repository->isFallbackCurrency($currency)) {
            throw new FireflyException('200026: Currency is fallback.'); // @codeCoverageIgnore
        }

        $this->repository->destroy($currency);

        return response()->json([], 204);
    }
}