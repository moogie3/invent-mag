@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.showToast('Success', '{{ session('success') }}', 'success');
        });
    </script>
@endif

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.showToast('Error', '{{ $errors->first() }}', 'error');
        });
    </script>
@endif
