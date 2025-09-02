<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <h2><i class="ti ti-bell me-2"></i>NOTIFICATION</h2>
            </div>
            <hr class="my-0">
            <div class="row g-0">
                <div class="col-12 col-md-3 border-end">
                    @include('admin.layouts.menu')
                </div>
                <div class="col-12 col-md-9">
                    <div class="card-body">
                        @include('admin.layouts.partials.notification.tabs')
                        @include('admin.layouts.partials.notification.content')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
