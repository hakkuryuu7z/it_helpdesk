<?php

namespace Tests\Feature\Tickets;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommentAndAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_internal_notes_are_never_visible_to_regular_users(): void
    {
        $branch = Branch::factory()->create();

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole(UserRole::RegularUser->value);

        $edp = User::factory()->create(['branch_id' => $branch->id]);
        $edp->assignRole(UserRole::BranchEdp->value);
        $edp->assignedBranches()->attach($branch->id);

        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
        ]);

        // 1 public comment, 1 internal note
        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $edp->id,
            'message' => 'Public note: Please reboot PC.',
            'is_internal' => false,
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $edp->id,
            'message' => 'INTERNAL NOTE: User forgot password again, suspected unauthorized attempt.',
            'is_internal' => true,
        ]);

        // Regular user views ticket
        $responseUser = $this->actingAs($user)->get(route('tickets.show', $ticket));
        $responseUser->assertOk();
        $responseUser->assertSee('Public note: Please reboot PC.');
        $responseUser->assertDontSee('INTERNAL NOTE: User forgot password');

        // EDP views ticket
        $responseEdp = $this->actingAs($edp)->get(route('tickets.show', $ticket));
        $responseEdp->assertOk();
        $responseEdp->assertSee('INTERNAL NOTE: User forgot password');
    }

    public function test_regular_user_cannot_create_internal_notes(): void
    {
        $branch = Branch::factory()->create();

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole(UserRole::RegularUser->value);

        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('tickets.comments.store', $ticket), [
            'message' => 'Attempting internal note',
            'is_internal' => 1,
        ]);

        // Form request authorization blocks regular users from setting is_internal
        $response->assertForbidden();
    }

    public function test_attachment_download_is_strictly_authorized(): void
    {
        $branch = Branch::factory()->create();

        $userA = User::factory()->create(['branch_id' => $branch->id]);
        $userA->assignRole(UserRole::RegularUser->value);

        $userB = User::factory()->create(['branch_id' => $branch->id]);
        $userB->assignRole(UserRole::RegularUser->value);

        $ticketA = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $userA->id,
        ]);

        $file = UploadedFile::fake()->create('network_diagnostic.pdf', 100, 'application/pdf');
        $storedPath = $file->storeAs("private/attachments/{$ticketA->id}", 'network_diagnostic.pdf', 'local');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticketA->id,
            'uploaded_by' => $userA->id,
            'file_path' => $storedPath,
            'original_name' => 'network_diagnostic.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400,
        ]);

        // Creator User A can download
        $responseA = $this->actingAs($userA)->get(route('attachments.download', $attachment));
        $responseA->assertOk();

        // Unrelated User B is Forbidden
        $responseB = $this->actingAs($userB)->get(route('attachments.download', $attachment));
        $responseB->assertForbidden();
    }

    public function test_executable_file_uploads_are_rejected(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole(UserRole::RegularUser->value);

        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
        ]);

        $fakeExe = UploadedFile::fake()->create('exploit.exe', 50, 'application/x-msdownload');

        $response = $this->actingAs($user)->post(route('tickets.comments.store', $ticket), [
            'message' => 'Check this file',
            'attachment' => $fakeExe,
        ]);

        $response->assertSessionHasErrors('attachment');
    }
}
