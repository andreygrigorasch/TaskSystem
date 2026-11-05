<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_parallel_status_and_assignment_cannot_silently_overwrite_each_other(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $replacement = User::factory()->create();
        $ticket = ServiceRequest::factory()->create();
        $connection = config('database.connections.'.config('database.default'));
        $env = [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_URL' => '',
            'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => $connection['database'], 'DB_USERNAME' => $connection['username'],
            'DB_PASSWORD' => $connection['password'] ?? '',
        ];
        $workers = [];
        DB::beginTransaction();
        try {
            ServiceRequest::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            foreach (['status', 'assignee'] as $operation) {
                $worker = new Process([PHP_BINARY, base_path('tests/Support/concurrent-update.php'), (string) $admin->id, (string) $ticket->id, $operation, (string) $replacement->id], base_path(), $env, null, 15);
                $worker->start();
                $workers[] = $worker;
            }
            $deadline = microtime(true) + 10;
            while (! array_reduce($workers, fn ($ready, $worker) => $ready && str_contains($worker->getOutput(), 'ready'), true)) {
                if (microtime(true) > $deadline) {
                    $this->fail('Workers did not reach the lock: '.implode(' ', array_map(fn ($worker) => $worker->getErrorOutput(), $workers)));
                }
                usleep(10000);
            }
            DB::commit();
            $results = [];
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput().$worker->getOutput());
                preg_match('/result:(\d+)/', $worker->getOutput(), $matches);
                $results[] = (int) ($matches[1] ?? 0);
            }
            sort($results);
            $this->assertSame([200, 409], $results);
            $fresh = $ticket->fresh();
            $this->assertSame(2, $fresh->version);
            $this->assertNotSame($fresh->status === RequestStatus::InProgress, $fresh->assignee_id === $replacement->id);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
        }
    }
}
