<?php

use App\Modules\Billing\Services\SequenceService;

it('génère des numéros séquentiels sans trou', function () {
    $service = app(SequenceService::class);
    $year = now()->year;

    expect($service->next('invoice', 'NETEJA'))->toBe("NETEJA-$year-000001")
        ->and($service->next('invoice', 'NETEJA'))->toBe("NETEJA-$year-000002")
        ->and($service->next('credit_note', 'NETEJA-AV'))->toBe("NETEJA-AV-$year-000001");
});
