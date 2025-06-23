@foreach ($items as $item)
    <div class="mb-2">
        <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
            <i class="ti {{ $item['icon'] }} fs-2"></i>
        </span>
        {{ $item['label'] }} : <strong>{{ $item['value'] }}</strong>
    </div>
@endforeach
