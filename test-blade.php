<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
$errors = new \Illuminate\Support\MessageBag(['customer_id' => 'The customer id field is required.']);
$viewErrorBag = new \Illuminate\Support\ViewErrorBag();
$viewErrorBag->put('default', $errors);
$html = view('admin.layouts.partials.session-notifications', ['errors' => $viewErrorBag])->render();
echo $html;
