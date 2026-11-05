<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_employee_cannot_read_or_change_another_employees_request(): void
    {
        $employee = User::factory()->create();
        $ticket = ServiceRequest::factory()->create();
        $this->actingAs($employee)->getJson("/api/requests/{$ticket->id}")->assertForbidden();
        $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'in_progress', 'version' => 1])->assertForbidden();
        $this->assertSame(1, $ticket->fresh()->version);
    }

    public function test_employee_list_and_pagination_exclude_other_requests(): void
    {
        $employee = User::factory()->create();
        $own = ServiceRequest::factory()->count(3)->create(['assignee_id' => $employee->id]);
        ServiceRequest::factory()->count(4)->create();
        ServiceRequest::factory()->create(['assignee_id' => null]);
        $first = $this->actingAs($employee)->getJson('/api/requests?per_page=2')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 3)->assertJsonPath('meta.last_page', 2);
        $first->assertJsonPath('data.0.id', $own->last()->id);
        $this->getJson('/api/requests?per_page=2&page=2')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_sees_every_request_and_filtering_is_server_side(): void
    {
        ServiceRequest::factory()->count(2)->create();
        ServiceRequest::factory()->create(['status' => 'done']);
        $this->actingAs($this->admin())->getJson('/api/requests')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/api/requests?status=done')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'done');
        $this->getJson('/api/requests?status=unknown')->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->getJson('/api/requests?per_page=101')->assertUnprocessable();
        $this->getJson('/api/requests?page=0')->assertUnprocessable();
    }

    public function test_skipping_a_status_is_rejected_without_changing_version(): void
    {
        $ticket = ServiceRequest::factory()->create();
        $this->actingAs($ticket->assignee)->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'done', 'version' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame(RequestStatus::New, $ticket->fresh()->status);
        $this->assertSame(1, $ticket->fresh()->version);
    }

    public function test_employee_can_perform_sequential_transitions(): void
    {
        $ticket = ServiceRequest::factory()->create();
        $this->actingAs($ticket->assignee)->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'in_progress', 'version' => 1])
            ->assertOk()->assertJsonPath('data.status', 'in_progress')->assertJsonPath('data.version', 2);
        $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'done', 'version' => 2])
            ->assertOk()->assertJsonPath('data.status', 'done')->assertJsonPath('data.version', 3);
    }

    public function test_repeated_and_backward_transitions_are_rejected(): void
    {
        $ticket = ServiceRequest::factory()->create(['status' => 'in_progress', 'version' => 2]);
        $this->actingAs($this->admin());
        foreach (['new', 'in_progress'] as $status) {
            $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => $status, 'version' => 2])->assertUnprocessable();
        }
        $this->assertSame(2, $ticket->fresh()->version);
    }

    public function test_stale_version_returns_conflict_without_overwriting_data(): void
    {
        $ticket = ServiceRequest::factory()->create();
        $this->actingAs($this->admin())->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'in_progress', 'version' => 1])->assertOk();
        $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'done', 'version' => 1])
            ->assertConflict()->assertJsonPath('code', 'version_conflict');
        $this->assertSame(RequestStatus::InProgress, $ticket->fresh()->status);
        $this->assertSame(2, $ticket->fresh()->version);
    }

    public function test_unauthenticated_user_cannot_access_or_modify_requests(): void
    {
        $ticket = ServiceRequest::factory()->create();
        $this->getJson('/api/requests')->assertUnauthorized();
        $this->getJson("/api/requests/{$ticket->id}")->assertUnauthorized();
        $this->postJson('/api/requests', ['title' => 'Test', 'description' => 'Test'])->assertUnauthorized();
        $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'in_progress', 'version' => 1])->assertUnauthorized();
        $this->patchJson("/api/requests/{$ticket->id}/assignee", ['assignee_id' => null, 'version' => 1])->assertUnauthorized();
    }

    public function test_completed_request_cannot_be_modified_even_by_admin(): void
    {
        $ticket = ServiceRequest::factory()->create(['status' => 'done', 'version' => 3]);
        $this->actingAs($this->admin());
        foreach (['new', 'in_progress', 'done'] as $status) {
            $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => $status, 'version' => 3])->assertUnprocessable();
        }
        $this->patchJson("/api/requests/{$ticket->id}/assignee", ['assignee_id' => null, 'version' => 3])->assertUnprocessable();
        $this->assertSame(3, $ticket->fresh()->version);
        $this->assertNotNull($ticket->fresh()->assignee_id);
    }

    public function test_admin_can_create_and_assign_a_request(): void
    {
        $employee = User::factory()->create();
        $response = $this->actingAs($this->admin())->postJson('/api/requests', [
            'title' => 'Проверить доступ', 'description' => 'Описание задачи', 'assignee_id' => $employee->id,
        ])->assertCreated()->assertJsonPath('data.status', 'new')->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.assignee.id', $employee->id)->assertJsonStructure(['data' => ['id', 'title', 'description', 'status', 'assignee', 'version', 'created_at', 'updated_at']]);
        $this->actingAs($employee)->getJson('/api/requests/'.$response->json('data.id'))->assertOk();
    }

    public function test_employee_cannot_create_assign_or_list_employees(): void
    {
        $ticket = ServiceRequest::factory()->create();
        $this->actingAs($ticket->assignee)->postJson('/api/requests', ['title' => 'Test', 'description' => 'Test'])->assertForbidden();
        $this->patchJson("/api/requests/{$ticket->id}/assignee", ['assignee_id' => null, 'version' => 1])->assertForbidden();
        $this->getJson('/api/employees')->assertForbidden();
    }

    public function test_reassignment_is_versioned_and_revokes_previous_employees_access(): void
    {
        $ticket = ServiceRequest::factory()->create();
        $original = $ticket->assignee;
        $replacement = User::factory()->create();
        $admin = $this->admin();
        $this->actingAs($admin)->patchJson("/api/requests/{$ticket->id}/assignee", ['assignee_id' => $replacement->id, 'version' => 1])
            ->assertOk()->assertJsonPath('data.version', 2)->assertJsonPath('data.assignee.id', $replacement->id);
        $this->patchJson("/api/requests/{$ticket->id}/assignee", ['assignee_id' => null, 'version' => 1])->assertConflict();
        $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'in_progress', 'version' => 1])->assertConflict();
        $this->actingAs($original)->getJson("/api/requests/{$ticket->id}")->assertForbidden();
        $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'in_progress', 'version' => 2])->assertForbidden();
        $this->actingAs($replacement)->getJson("/api/requests/{$ticket->id}")->assertOk();
    }

    public function test_unassigned_request_and_employee_directory_are_available_to_admin(): void
    {
        $ticket = ServiceRequest::factory()->create(['assignee_id' => null]);
        User::factory()->count(2)->create();
        $this->actingAs($this->admin())->getJson('/api/employees')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/requests/{$ticket->id}")->assertOk()->assertJsonPath('data.assignee', null);
    }

    public function test_validation_rejects_invalid_inputs_and_missing_versions(): void
    {
        $admin = $this->admin();
        $ticket = ServiceRequest::factory()->create();
        $this->actingAs($admin)->postJson('/api/requests', ['title' => '', 'description' => ''])->assertUnprocessable()->assertJsonValidationErrors(['title', 'description']);
        $this->postJson('/api/requests', ['title' => str_repeat('x', 201), 'description' => 'test'])->assertUnprocessable();
        foreach ([$admin->id, 999999] as $assignee) {
            $this->postJson('/api/requests', ['title' => 'test', 'description' => 'test', 'assignee_id' => $assignee])->assertUnprocessable()->assertJsonValidationErrors('assignee_id');
        }
        $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'in_progress'])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->patchJson("/api/requests/{$ticket->id}/status", ['status' => 'unknown', 'version' => 1])->assertUnprocessable();
        $this->patchJson("/api/requests/{$ticket->id}/assignee", ['version' => 1])->assertUnprocessable();
        $this->postJson('/api/requests', ['title' => 'test', 'description' => 'test', 'status' => 'done', 'version' => 10])->assertUnprocessable();
    }

    public function test_missing_request_and_unknown_api_route_return_json_404(): void
    {
        $this->actingAs($this->admin())->getJson('/api/requests/999999')->assertNotFound();
        $this->get('/api/unknown')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_arbitrary_payload_cannot_change_ownership_or_content_through_status_endpoint(): void
    {
        $ticket = ServiceRequest::factory()->create();
        $title = $ticket->title;
        $this->actingAs($ticket->assignee)->patchJson("/api/requests/{$ticket->id}/status", [
            'status' => 'in_progress', 'version' => 1, 'assignee_id' => null, 'title' => 'Injected',
        ])->assertOk()->assertJsonPath('data.title', $title)->assertJsonPath('data.assignee_id', $ticket->assignee_id);
    }
}
