<?php

namespace Tests\Feature\Tickets;

use App\Services\TicketNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_number_follows_required_format(): void
    {
        $service = app(TicketNumberService::class);
        $currentYear = (int) now()->format('Y');

        $ticketNumber = $service->generateNextTicketNumber();

        $expectedPrefix = sprintf('HD-%d-', $currentYear);
        $this->assertStringStartsWith($expectedPrefix, $ticketNumber);
        $this->assertEquals(sprintf('HD-%d-000001', $currentYear), $ticketNumber);
    }

    public function test_ticket_numbers_increment_monotonically(): void
    {
        $service = app(TicketNumberService::class);
        $currentYear = (int) now()->format('Y');

        $num1 = $service->generateNextTicketNumber();
        $num2 = $service->generateNextTicketNumber();
        $num3 = $service->generateNextTicketNumber();

        $this->assertEquals(sprintf('HD-%d-000001', $currentYear), $num1);
        $this->assertEquals(sprintf('HD-%d-000002', $currentYear), $num2);
        $this->assertEquals(sprintf('HD-%d-000003', $currentYear), $num3);
    }
}
