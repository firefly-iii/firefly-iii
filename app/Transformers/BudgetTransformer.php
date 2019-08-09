<?php
/**
 * BudgetTransformer.php
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


use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BudgetTransformer
 */
class BudgetTransformer extends AbstractTransformer
{
    private $repository;

    /**
     * BudgetTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->repository = app(BudgetRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Transform a budget.
     *
     * @param Budget $budget
     *
     * @return array
     */
    public function transform(Budget $budget): array
    {
        $this->repository->setUser($budget->user);
        $start = $this->parameters->get('start');
        $end   = $this->parameters->get('end');
        $spent = [];
        if (null !== $start && null !== $end) {
            $spent = $this->repository->spentInPeriodMc(new Collection([$budget]), new Collection, $start, $end);
        }

        $data = [
            'id'         => (int)$budget->id,
            'created_at' => $budget->created_at->toAtomString(),
            'updated_at' => $budget->updated_at->toAtomString(),
            'active'     => $budget->active,
            'name'       => $budget->name,
            'spent'      => $spent,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/budgets/' . $budget->id,
                ],
            ],
        ];

        return $data;
    }

}
