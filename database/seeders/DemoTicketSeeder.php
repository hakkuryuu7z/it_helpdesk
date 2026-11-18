<?php

namespace Database\Seeders;

use App\Actions\FeatureRequests\CreateFeatureRequestAction;
use App\Actions\Tickets\CreateTicketAction;
use App\Actions\Tickets\TransitionTicketStatusAction;
use App\Enums\FeatureRequestStage;
use App\Enums\TicketImpact;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Models\Branch;
use App\Models\FeatureRequestActivity;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTicketSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@helpdesk.local')->first();
        $centralEdp = User::where('email', 'central.edp@helpdesk.local')->first();
        $edpBdg = User::where('email', 'edp.bdg@helpdesk.local')->first();
        $edpSby = User::where('email', 'edp.sby@helpdesk.local')->first();

        $userBdg = User::where('email', 'user.bdg@helpdesk.local')->first();
        $userSby = User::where('email', 'user.sby@helpdesk.local')->first();
        $userHq = User::where('email', 'user.hq@helpdesk.local')->first();

        $catNetwork = TicketCategory::where('name', 'like', '%Network%')->first();
        $catHardware = TicketCategory::where('name', 'like', '%Printer%')->first();
        $catAccount = TicketCategory::where('name', 'like', '%New User%')->first();
        $catFeature = TicketCategory::where('name', 'like', '%Financial%')->first();

        $createAction = app(CreateTicketAction::class);
        $transitionAction = app(TransitionTicketStatusAction::class);

        // 1. Critical Incident in Bandung: Warehouse Core Switch Failure (In Progress)
        if ($userBdg && $catNetwork && $edpBdg) {
            $t1 = $createAction->execute([
                'type' => TicketType::Incident,
                'category_id' => $catNetwork->id,
                'subject' => 'Core Switch Stacking Failure in Main Warehouse',
                'description' => "At 08:45 AM, primary distribution switch stack lost heartbeat connection. Handheld barcode scanners and dispatch printing terminals are disconnected.\n\nSwitch IP: 192.168.10.2\nError LED: Amber flashing on Unit 1 Master.",
                'impact' => TicketImpact::High,
                'urgency' => TicketUrgency::High,
            ], $userBdg);

            $t1->update(['assigned_to' => $edpBdg->id]);
            $transitionAction->execute($t1, TicketStatus::InProgress, $edpBdg);

            TicketComment::create([
                'ticket_id' => $t1->id,
                'user_id' => $edpBdg->id,
                'message' => 'Dispatched technician to rack B-02. Backup power supply inspected, preparing failover to Unit 2 stack slave.',
                'is_internal' => true,
            ]);

            TicketComment::create([
                'ticket_id' => $t1->id,
                'user_id' => $edpBdg->id,
                'message' => 'We are currently on-site investigating the warehouse network switch. Estimated resolution time is 30 minutes.',
                'is_internal' => false,
            ]);
        }

        // 2. High Priority Service Request in Surabaya: New Branch Manager ERP Provisioning (Resolved)
        if ($userSby && $catAccount && $edpSby) {
            $t2 = $createAction->execute([
                'type' => TicketType::ServiceRequest,
                'category_id' => $catAccount->id,
                'subject' => 'ERP & Financial Authorization Access for New Branch Manager',
                'description' => "Please configure ERP authorization profile for Budi Santoso (NIP: SBY-8821). Needs Purchase Order approval ceiling up to Rp 500,000,000 and Journal Voucher view rights.",
                'impact' => TicketImpact::Medium,
                'urgency' => TicketUrgency::High,
            ], $userSby);

            $t2->update(['assigned_to' => $edpSby->id]);
            $transitionAction->execute($t2, TicketStatus::InProgress, $edpSby);
            $transitionAction->execute($t2, TicketStatus::Resolved, $edpSby, 'ERP user account created with Approval Profile LEVEL-3. Verification email sent with initial temporary credentials.');
        }

        // 3. Medium Incident in HQ: Thermal Printer Paper Jam (Open / Waiting Assignment)
        if ($userHq && $catHardware) {
            $createAction->execute([
                'type' => TicketType::Incident,
                'category_id' => $catHardware->id,
                'subject' => 'Billing Counter Thermal Printer Cutter Jammed',
                'description' => "The Epson TM-T88 thermal printer at counter 3 has jammed auto-cutter mechanism. Red paper out indicator stays on even with fresh roll installed.",
                'impact' => TicketImpact::Low,
                'urgency' => TicketUrgency::Medium,
            ], $userHq);
        }

        // 4. Feature Request: Automated Bank Reconciliation Module (In Development Stage)
        if ($userHq && $catFeature && $centralEdp) {
            $t4 = $createAction->execute([
                'type' => TicketType::FeatureRequest,
                'category_id' => $catFeature->id,
                'subject' => 'Automated Bank Reconciliation & Statement Parser',
                'description' => 'Need automated parser for MT940 and CSV bank statements to reconcile against ERP general ledger entries.',
                'business_requirements' => "1. Support daily import of Mandiri, BCA, and BRI MT940 formats.\n2. Automatic transaction matching algorithm based on date (+/- 2 days), amount, and invoice reference pattern.\n3. Discrepancy exception queue for accounting review.",
                'problem_description' => 'Accounting staff spends 3 full workdays every month manually matching bank statements against accounting journals.',
                'expected_outcome' => 'Reduce month-end closing reconciliation from 3 days to under 45 minutes with 98% automated matching precision.',
                'impact' => TicketImpact::High,
                'urgency' => TicketUrgency::Medium,
            ], $userHq);

            if ($fr = $t4->featureRequest) {
                $fr->update([
                    'stage' => FeatureRequestStage::Development,
                    'dev_priority' => 'high',
                    'estimated_effort' => '2 Sprints (4 weeks)',
                    'technical_notes' => 'Implemented MT940 tokenizer using league/csv parser. Database schema includes bank_reconciliations and bank_statement_lines tables with unique hash constraints.',
                ]);

                FeatureRequestActivity::create([
                    'feature_request_id' => $fr->id,
                    'user_id' => $centralEdp->id,
                    'from_stage' => FeatureRequestStage::Submitted,
                    'to_stage' => FeatureRequestStage::UnderReview,
                    'notes' => 'Architectural feasibility approved during sprint grooming.',
                    'created_at' => now()->subDays(5),
                ]);

                FeatureRequestActivity::create([
                    'feature_request_id' => $fr->id,
                    'user_id' => $centralEdp->id,
                    'from_stage' => FeatureRequestStage::UnderReview,
                    'to_stage' => FeatureRequestStage::Accepted,
                    'notes' => 'Approved by Head of IT and CFO.',
                    'created_at' => now()->subDays(4),
                ]);

                FeatureRequestActivity::create([
                    'feature_request_id' => $fr->id,
                    'user_id' => $centralEdp->id,
                    'from_stage' => FeatureRequestStage::Accepted,
                    'to_stage' => FeatureRequestStage::Development,
                    'notes' => 'Core parser backend and migration in progress by central software team.',
                    'created_at' => now()->subDays(2),
                ]);

                $t4->update(['status' => TicketStatus::InProgress]);
            }
        }
    }
}
