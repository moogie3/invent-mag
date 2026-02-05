<thead style="font-size: large">
    <tr>
        <th class="sticky-top " style="z-index: 1020;">
            <input type="checkbox" id="selectAll" class="form-check-input">
        </th>
        <th class="sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-no">{{ __('messages.no') }}</button>
        </th>
        <th class="sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3">{{ __('messages.picture') }}</button>
        </th>
        <th class="no-print sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-code">{{ __('messages.code') }}</button>
        </th>
        <th class="sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-name">{{ __('messages.name') }}</button>
        </th>
        <th class="no-print sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-quantity">
                {{ isset($selectedWarehouse) ? __('messages.qty') . ' (' . $selectedWarehouse->name . ')' : __('messages.qty') . ' (' . __('messages.total') . ')' }}
            </button>
        </th>
        <th class="no-print sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-category">{{ __('messages.cat') }}</button>
        </th>
        <th class="sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-unit">{{ __('messages.unit') }}</button>
        </th>
        <th class="sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-price">{{ __('messages.price') }}</button>
        </th>
        <th class="sticky-top " style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-sellingprice">{{ __('messages.product_selling_price') }}</button>
        </th>
        <th class="sticky-top text-center" style="z-index: 1020;">
            <button class="table-sort fs-4 py-3" data-sort="sort-supplier">{{ __('messages.supplier') }}</button>
        </th>
        <th style="width:100px;text-align:center" class="fs-4 py-3 no-print sticky-top " style="z-index: 1020;">
            {{ __('messages.action') }}
        </th>
    </tr>
</thead>
