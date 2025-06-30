<div id="invoiceTableContainer">
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead style="font-size: large">
                <tr>
                    <th class="no-print">
                        <button class="table-sort fs-4 py-3 no-print" data-sort="sort-no">No</button>
                    </th>
                    <th>
                        <button class="table-sort fs-4 py-3" data-sort="sort-name">Name</button>
                    </th>
                    <th>
                        <button class="table-sort fs-4 py-3" data-sort="sort-address">Address</button>
                    </th>
                    <th>
                        <button class="table-sort fs-4 py-3" data-sort="sort-description">Description</button>
                    </th>
                    <th>
                        <button class="table-sort fs-4 py-3" data-sort="sort-is-main">Main</button>
                    </th>
                    <th style="width:180px;text-align:center" class="fs-4 py-3 no-print">Action</th>
                </tr>
            </thead>
            <tbody id="invoiceTableBody" class="table-tbody">
                @foreach ($wos as $index => $wo)
                    @include('admin.layouts.partials.warehouse.index.table-row', [
                        'wo' => $wo,
                        'index' => $index,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>
</div>
