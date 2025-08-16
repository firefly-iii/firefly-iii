<?php

namespace Tests\integration\Api\Chart;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;

class BudgetControllerTest extends TestCase
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
        $response = $this->getJson(route('api.v1.chart.budget.overview'));
        $response->assertStatus(422);

    }
    public function testGetOverviewChart(): void
    {
        $this->actingAs($this->user);
        $params = [
            'start' => '2024-01-01',
            'end'   => '2024-01-31',
        ];
        $response = $this->getJson(route('api.v1.chart.budget.overview') . '?' . http_build_query($params));
        $response->assertStatus(200);

    }
}
