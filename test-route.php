<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap(); // load config

// Try directly invoking the exception handler with a ValidationException
$handler = $app->make(Illuminate\Contracts\Debug\ExceptionHandler::class);

$request = Illuminate\Http\Request::create('/test-validate', 'POST', [], [], [], [
    'HTTP_ACCEPT' => 'text/vnd.turbo-stream.html, text/html, application/xhtml+xml',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
]);

$e = Illuminate\Validation\ValidationException::withMessages(['foo' => 'required']);
$response = $handler->render($request, $e);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
