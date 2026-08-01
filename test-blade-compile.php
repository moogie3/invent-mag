<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('router')->get('/test-view', function () {
    $errors = new \Illuminate\Support\MessageBag(['customer_id' => 'The customer id field is required.']);
    $viewErrorBag = new \Illuminate\Support\ViewErrorBag();
    $viewErrorBag->put('default', $errors);
    return view('admin.layouts.partials.session-notifications')->with('errors', $viewErrorBag);
});
$request = Illuminate\Http\Request::create('/test-view', 'GET');
$response = $kernel->handle($request);
echo $response->getContent();
