<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$api = app(App\Services\MailcowService::class);
$res = \Illuminate\Support\Facades\Http::withHeaders([
    'X-API-Key' => config('mailcow.api_key')
])->get(rtrim(config('mailcow.url'), '/') . '/api/v1/get/mailbox/all')->json();

foreach ($res as $m) {
    $email = $m['username'] ?? '';
    if (str_contains($email, '2425071210')) {
        echo $email . "\n";
    }
}
