<?php
/**
 * UserTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;


use FireflyIII\Models\Role;
use FireflyIII\User;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class UserTransformer
 */
class UserTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include.
     *
     * @var array
     */
    protected $availableIncludes = ['accounts', 'attachments', 'bills', 'budgets', 'categories', 'piggy_banks', 'tags', 'transactions'];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * UserTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Include accounts.
     *
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return FractalCollection
     */
    public function includeAccounts(User $user): FractalCollection
    {
        return $this->collection($user->accounts, new AccountTransformer($this->parameters), 'accounts');
    }

    /**
     * Include attachments.
     *
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return FractalCollection
     */
    public function includeAttachments(User $user): FractalCollection
    {
        return $this->collection($user->attachments, new AttachmentTransformer($this->parameters), 'attachments');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return FractalCollection
     */
    public function includeBills(User $user): FractalCollection
    {
        return $this->collection($user->bills, new BillTransformer($this->parameters), 'bills');
    }

    /**
     * Include budgets.
     *
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return FractalCollection
     */
    public function includeBudgets(User $user): FractalCollection
    {
        return $this->collection($user->budgets, new BudgetTransformer($this->parameters), 'budgets');
    }

    /**
     * Include categories.
     *
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return FractalCollection
     */
    public function includeCategories(User $user): FractalCollection
    {
        return $this->collection($user->categories, new CategoryTransformer($this->parameters), 'categories');
    }

    /**
     * Include piggy banks.
     *
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return FractalCollection
     */
    public function includePiggyBanks(User $user): FractalCollection
    {
        return $this->collection($user->piggyBanks, new PiggyBankTransformer($this->parameters), 'piggy_banks');
    }

    /**
     * Include tags.
     *
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return FractalCollection
     */
    public function includeTags(User $user): FractalCollection
    {
        return $this->collection($user->tags, new TagTransformer($this->parameters), 'tags');
    }

    /**
     * Include transactions.
     *
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return FractalCollection
     */
    public function includeTransactions(User $user): FractalCollection
    {
        return $this->collection($user->transactions, new TransactionTransformer($this->parameters), 'transactions');
    }

    /**
     * Transform user.
     *
     * @param User $user
     *
     * @return array
     */
    public function transform(User $user): array
    {
        /** @var Role $role */
        $role = $user->roles()->first();
        if (null !== $role) {
            $role = $role->name;
        }

        return [
            'id'           => (int)$user->id,
            'updated_at'   => $user->updated_at->toAtomString(),
            'created_at'   => $user->created_at->toAtomString(),
            'email'        => $user->email,
            'blocked'      => 1 === (int)$user->blocked,
            'blocked_code' => $user->blocked_code,
            'role'         => $role,
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => '/users/' . $user->id,
                ],
            ],
        ];
    }

}
