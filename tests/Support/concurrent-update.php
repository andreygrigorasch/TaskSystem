<?php

use App\Actions\UpdateServiceRequest;
use App\Enums\RequestStatus;
use App\Exceptions\VersionConflict;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Auth::loginUsingId((int) $argv[1]);
fwrite(STDOUT, "ready\n");
fflush(STDOUT);
try {
    $action = app(UpdateServiceRequest::class);
    if ($argv[3] === 'status') {
        $action->status((int) $argv[2], 1, RequestStatus::InProgress);
    } else {
        $action->assignee((int) $argv[2], 1, (int) $argv[4]);
    }
    fwrite(STDOUT, "result:200\n");
} catch (VersionConflict) {
    fwrite(STDOUT, "result:409\n");
}
