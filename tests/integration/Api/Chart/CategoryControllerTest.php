<?php


/*
 * CategoryControllerTest.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace Tests\integration\Api\Chart;

use Override;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;
    private $user;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (!isset($this->user)) {
            $this->user = $this->createAuthenticatedUser();
        }
        $this->actingAs($this->user);
    }

    public function testGetOverviewChartFails(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.chart.category.overview'));
        $response->assertStatus(422);

    }

    public function testGetOverviewChart(): void
    {
        $this->actingAs($this->user);
        $params   = [
            'start' => '2024-01-01',
            'end'   => '2024-01-31',
        ];
        $response = $this->getJson(route('api.v1.chart.category.overview').'?'.http_build_query($params));
        $response->assertStatus(200);

    }
}
