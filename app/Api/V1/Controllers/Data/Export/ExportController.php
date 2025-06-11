<?php

/*
 * ExportController.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Data\Export;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\Export\ExportRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Export\ExportDataGenerator;
use Illuminate\Http\Response as LaravelResponse;
use Safe\Exceptions\DatetimeException;

use function Safe\date;

/**
 * Class ExportController
 */
class ExportController extends Controller
{
    private ExportDataGenerator $exporter;

    /**
     * ExportController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->exporter = app(ExportDataGenerator::class);
                $this->exporter->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportAccounts
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function accounts(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportAccounts(true);

        return $this->returnExport('accounts');
    }

    /**
     * @throws FireflyException
     * @throws DatetimeException
     */
    private function returnExport(string $key): LaravelResponse
    {
        $date     = date('Y-m-d-H-i-s');
        $fileName = sprintf('%s-export-%s.csv', $date, $key);
        $data     = $this->exporter->export();

        /** @var LaravelResponse $response */
        $response = response($data[$key]);
        $response
            ->header('Content-Description', 'File Transfer')
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename='.$fileName)
            ->header('Content-Transfer-Encoding', 'binary')
            ->header('Connection', 'Keep-Alive')
            ->header('Expires', '0')
            ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->header('Pragma', 'public')
            ->header('Content-Length', (string) strlen((string) $data[$key]))
        ;

        return $response;
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportBills
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function bills(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportBills(true);

        return $this->returnExport('bills');
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportBudgets
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function budgets(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportBudgets(true);

        return $this->returnExport('budgets');
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportCategories
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function categories(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportCategories(true);

        return $this->returnExport('categories');
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportPiggies
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function piggyBanks(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportPiggies(true);

        return $this->returnExport('piggies');
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportRecurring
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function recurring(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportRecurring(true);

        return $this->returnExport('recurrences');
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportRules
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function rules(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportRules(true);

        return $this->returnExport('rules');
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportTags
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function tags(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportTags(true);

        return $this->returnExport('tags');
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/exportTransactions
     *
     * @throws FireflyException
     */
    public function transactions(ExportRequest $request): LaravelResponse
    {
        $params = $request->getAll();
        $this->exporter->setStart($params['start']);
        $this->exporter->setEnd($params['end']);
        $this->exporter->setAccounts($params['accounts']);
        $this->exporter->setExportTransactions(true);

        return $this->returnExport('transactions');
    }
}
