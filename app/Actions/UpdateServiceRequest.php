<?php

namespace App\Actions;

use App\Enums\RequestStatus;
use App\Exceptions\VersionConflict;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateServiceRequest
{
    public function status(int $id, int $version, RequestStatus $status): ServiceRequest
    {
        return $this->update($id, $version, 'updateStatus', function (ServiceRequest $ticket) use ($status) {
            if ($ticket->status->next() !== $status) {
                throw ValidationException::withMessages([
                    'status' => 'Допустим только следующий статус: новая → в работе → завершена.',
                ]);
            }
            $ticket->status = $status;
        });
    }

    public function assignee(int $id, int $version, ?int $assigneeId): ServiceRequest
    {
        return $this->update($id, $version, 'assign', function (ServiceRequest $ticket) use ($assigneeId) {
            $ticket->assignee_id = $assigneeId;
        });
    }

    private function update(int $id, int $version, string $ability, callable $change): ServiceRequest
    {
        return DB::transaction(function () use ($id, $version, $ability, $change) {
            // Authorization, version and status use the current row, including
            // after a concurrent reassignment. The lock is held until commit.
            $ticket = ServiceRequest::query()->lockForUpdate()->findOrFail($id);
            Gate::authorize($ability, $ticket);
            if ($ticket->version !== $version) {
                throw new VersionConflict;
            }
            if ($ticket->status === RequestStatus::Done) {
                throw ValidationException::withMessages(['status' => 'Завершённую заявку нельзя изменять.']);
            }
            $change($ticket);
            $ticket->version++;
            $ticket->save();

            return $ticket->load('assignee');
        });
    }
}
