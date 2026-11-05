<?php

namespace App\Http\Controllers;

use App\Actions\UpdateServiceRequest;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate([
            'status' => ['sometimes', Rule::enum(RequestStatus::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $tickets = ServiceRequest::query()->visibleTo($request->user())->with('assignee')
            ->when(isset($input['status']), fn ($query) => $query->where('status', $input['status']))
            ->orderByDesc('id')->paginate($input['per_page'] ?? 10)->withQueryString();

        return ServiceRequestResource::collection($tickets);
    }

    public function show(ServiceRequest $ticket): ServiceRequestResource
    {
        Gate::authorize('view', $ticket);

        return new ServiceRequestResource($ticket->load('assignee'));
    }

    public function store(Request $request): ServiceRequestResource
    {
        Gate::authorize('create', ServiceRequest::class);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'assignee_id' => $this->assigneeRules(),
            'status' => ['prohibited'],
            'version' => ['prohibited'],
        ]);
        $ticket = ServiceRequest::create($data)->refresh()->load('assignee');

        return new ServiceRequestResource($ticket);
    }

    public function status(Request $request, ServiceRequest $ticket, UpdateServiceRequest $update): ServiceRequestResource
    {
        Gate::authorize('updateStatus', $ticket);
        $input = $request->validate([
            'status' => ['required', Rule::enum(RequestStatus::class)],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        return new ServiceRequestResource($update->status($ticket->id, (int) $input['version'], RequestStatus::from($input['status'])));
    }

    public function assignee(Request $request, ServiceRequest $ticket, UpdateServiceRequest $update): ServiceRequestResource
    {
        Gate::authorize('assign', $ticket);
        $input = $request->validate([
            'assignee_id' => ['present', ...$this->assigneeRules()],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        return new ServiceRequestResource($update->assignee($ticket->id, (int) $input['version'], isset($input['assignee_id']) ? (int) $input['assignee_id'] : null));
    }

    public function employees()
    {
        Gate::authorize('create', ServiceRequest::class);

        return response()->json(['data' => User::where('role', Role::Employee)->orderBy('name')->get(['id', 'name', 'email'])]);
    }

    private function assigneeRules(): array
    {
        return ['nullable', 'integer', Rule::exists('users', 'id')->where('role', Role::Employee->value)];
    }
}
