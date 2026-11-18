<?php

namespace Database\Seeders;

use App\Enums\TicketType;
use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Incidents
            ['name' => 'Network & Internet Connection', 'type' => TicketType::Incident->value],
            ['name' => 'Hardware Failure (PC, Laptop, Monitor)', 'type' => TicketType::Incident->value],
            ['name' => 'Printer, Scanner & Barcode Issues', 'type' => TicketType::Incident->value],
            ['name' => 'POS & Web Application Errors', 'type' => TicketType::Incident->value],
            ['name' => 'Operating System & Virus/Malware', 'type' => TicketType::Incident->value],

            // Service Requests
            ['name' => 'New User Account & System Access', 'type' => TicketType::ServiceRequest->value],
            ['name' => 'Email Account & Group Permission', 'type' => TicketType::ServiceRequest->value],
            ['name' => 'Software Installation & Licensing', 'type' => TicketType::ServiceRequest->value],
            ['name' => 'Hardware Relocation & Office Setup', 'type' => TicketType::ServiceRequest->value],
            ['name' => 'Network Port & WiFi Provisioning', 'type' => TicketType::ServiceRequest->value],

            // Feature Requests
            ['name' => 'POS & Sales Feature Enhancement', 'type' => TicketType::FeatureRequest->value],
            ['name' => 'Inventory & Stock Reporting', 'type' => TicketType::FeatureRequest->value],
            ['name' => 'Financial Export & Automation', 'type' => TicketType::FeatureRequest->value],
            ['name' => 'Operational Dashboard & Analytics', 'type' => TicketType::FeatureRequest->value],
        ];

        foreach ($categories as $cat) {
            TicketCategory::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'type' => $cat['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
