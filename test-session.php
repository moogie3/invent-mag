<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a fake route to test validation
$app->make('router')->post('/test-post', function (Illuminate\Http\Request $req) {
    $req->validate(['foo' => 'required']);
});

$request = Illuminate\Http\Request::create('/test-post', 'POST', [], [], [], [
    'HTTP_ACCEPT' => 'text/html',
]);
$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Location: " . $response->headers->get('Location') . "\n";
