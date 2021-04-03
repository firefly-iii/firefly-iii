<?php
declare(strict_types=1);
/*
 * AccountController.php
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

namespace FireflyIII\Api\V1\Controllers\Data\Export;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\Export\ExportRequest;
use FireflyIII\Support\Export\ExportDataGenerator;
use FireflyIII\User;
use Illuminate\Http\Response as LaravelResponse;
use League\Csv\CannotInsertRecord;

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
                /** @var User $user */
                $user = auth()->user();
                /** @var ExportDataGenerator $exporter */
                $this->exporter = app(ExportDataGenerator::class);
                $this->exporter->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
     */
    public function accounts(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportAccounts(true);

        return $this->returnExport('accounts');

    }

    /**
     * @param string $key
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
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
            ->header('Content-Disposition', 'attachment; filename=' . $fileName)
            ->header('Content-Transfer-Encoding', 'binary')
            ->header('Connection', 'Keep-Alive')
            ->header('Expires', '0')
            ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->header('Pragma', 'public')
            ->header('Content-Length', strlen($data[$key]));

        return $response;
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
     */
    public function bills(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportBills(true);

        return $this->returnExport('bills');
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
     */
    public function budgets(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportBudgets(true);

        return $this->returnExport('budgets');
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
     */
    public function categories(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportCategories(true);

        return $this->returnExport('categories');
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
     */
    public function piggyBanks(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportPiggies(true);

        return $this->returnExport('piggies');
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
     */
    public function recurring(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportRecurring(true);

        return $this->returnExport('recurrences');
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
     */
    public function rules(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportRules(true);

        return $this->returnExport('rules');
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
     */
    public function tags(ExportRequest $request): LaravelResponse
    {
        $this->exporter->setExportTags(true);

        return $this->returnExport('tags');
    }

    /**
     * @param ExportRequest $request
     *
     * @return LaravelResponse
     * @throws CannotInsertRecord
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
