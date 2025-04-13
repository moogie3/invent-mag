@extends('admin.layouts.errorbase')

@section('title', 'Error 429')

@section('content')
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark">
                        <i class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG
                    </a>
                </div>
                <div class="empty-header">429</div>
                <p class="empty-title">Too Many Requests</p>
                <p class="empty-subtitle text-secondary">
                    You have made too many requests in a short period. Please wait
                    <span id="countdown">120</span> seconds before trying again.
                </p>
            </div>
        </div>
    </div>

    <script>
        let timeLeft = 60; // 2 minutes
        let countdownElement = document.getElementById("countdown");

        countdownElement.textContent = timeLeft;

        let countdownTimer = setInterval(function() {
            timeLeft--;
            countdownElement.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                location.reload(); // Auto refresh when time is up
            }
        }, 1000);
    </script>
@endsection
