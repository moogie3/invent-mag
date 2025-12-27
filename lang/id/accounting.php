<?php

return [
    'accounts' => [
        'assets' => [
            'name' => 'Aset',
        ],
        'current_assets' => [
            'name' => 'Aset Lancar',
        ],
        'cash' => [
            'name' => 'Kas',
            'description' => 'Kas di tangan dan di rekening bank.',
        ],
        'bank' => [
            'name' => 'Bank',
            'description' => 'Dana yang disimpan di rekening bank.',
        ],
        'accounts_receivable' => [
            'name' => 'Piutang Usaha',
            'description' => 'Uang yang terutang kepada perusahaan oleh pelanggannya.',
        ],
        'allowance_for_doubtful_accounts' => [
            'name' => 'Penyisihan Piutang Tak Tertagih',
            'description' => 'Estimasi jumlah piutang usaha yang mungkin tidak dapat ditagih.',
        ],
        'inventory' => [
            'name' => 'Persediaan',
            'description' => 'Nilai barang yang tersedia untuk dijual.',
        ],
        'prepaid_expenses' => [
            'name' => 'Beban Dibayar di Muka',
            'description' => 'Beban yang dibayar di muka untuk manfaat di masa depan.',
        ],
        'input_vat' => [
            'name' => 'PPN Masukan',
            'description' => 'Pajak Pertambahan Nilai yang dibayar atas pembelian.',
        ],
        'fixed_assets' => [
            'name' => 'Aset Tetap',
        ],
        'land' => [
            'name' => 'Tanah',
            'description' => 'Tanah yang dimiliki oleh perusahaan.',
        ],
        'buildings' => [
            'name' => 'Bangunan',
            'description' => 'Bangunan yang dimiliki oleh perusahaan.',
        ],
        'accumulated_depreciation_buildings' => [
            'name' => 'Akumulasi Penyusutan - Bangunan',
            'description' => 'Total beban penyusutan yang dicatat untuk bangunan.',
        ],
        'equipment' => [
            'name' => 'Peralatan',
            'description' => 'Peralatan yang dimiliki oleh perusahaan.',
        ],
        'accumulated_depreciation_equipment' => [
            'name' => 'Akumulasi Penyusutan - Peralatan',
            'description' => 'Total beban penyusutan yang dicatat untuk peralatan.',
        ],

        'liabilities' => [
            'name' => 'Liabilitas',
        ],
        'current_liabilities' => [
            'name' => 'Liabilitas Lancar',
        ],
        'accounts_payable' => [
            'name' => 'Utang Usaha',
            'description' => 'Uang yang terutang oleh perusahaan kepada pemasoknya.',
        ],
        'accrued_expenses' => [
            'name' => 'Beban Akrual',
            'description' => 'Beban yang telah terjadi tetapi belum dibayar.',
        ],
        'output_vat' => [
            'name' => 'PPN Keluaran',
            'description' => 'Pajak Pertambahan Nilai yang dipungut atas penjualan.',
        ],
        'long_term_liabilities' => [
            'name' => 'Liabilitas Jangka Panjang',
        ],
        'bank_loans' => [
            'name' => 'Pinjaman Bank',
            'description' => 'Pinjaman dari bank dengan jatuh tempo lebih dari satu tahun.',
        ],

        'equity' => [
            'name' => 'Ekuitas',
        ],
        'owners_equity' => [
            'name' => 'Ekuitas Pemilik',
            'description' => 'Bagian pemilik dalam perusahaan.',
        ],
        'retained_earnings' => [
            'name' => 'Laba Ditahan',
            'description' => 'Akumulasi laba bersih yang ditahan oleh perusahaan.',
        ],

        'revenue' => [
            'name' => 'Pendapatan',
        ],
        'sales_revenue' => [
            'name' => 'Pendapatan Penjualan',
            'description' => 'Pendapatan yang dihasilkan dari penjualan barang atau jasa.',
        ],
        'sales_returns' => [
            'name' => 'Retur Penjualan',
            'description' => 'Barang yang dikembalikan oleh pelanggan.',
        ],
        'sales_discounts' => [
            'name' => 'Diskon Penjualan',
            'description' => 'Diskon yang diberikan kepada pelanggan untuk pembayaran awal atau pembelian dalam jumlah besar.',
        ],

        'cost_of_goods_sold_group' => [
            'name' => 'Harga Pokok Penjualan',
        ],
        'purchases' => [
            'name' => 'Pembelian',
            'description' => 'Biaya barang yang dibeli untuk dijual kembali.',
        ],
        'freight_in' => [
            'name' => 'Beban Angkut Pembelian',
            'description' => 'Biaya pengangkutan barang yang dibeli ke bisnis.',
        ],
        'purchase_returns' => [
            'name' => 'Retur Pembelian',
            'description' => 'Barang yang dikembalikan kepada pemasok.',
        ],
        'purchase_discounts' => [
            'name' => 'Diskon Pembelian',
            'description' => 'Diskon yang diterima dari pemasok untuk pembayaran awal atau pembelian dalam jumlah besar.',
        ],
        'cost_of_goods_sold' => [
            'name' => 'Beban Pokok Penjualan',
            'description' => 'Biaya langsung yang dapat diatribusikan pada produksi barang yang dijual oleh perusahaan.',
        ],

        'expenses' => [
            'name' => 'Beban',
        ],
        'selling_expenses' => [
            'name' => 'Beban Penjualan',
        ],
        'salaries_selling' => [
            'name' => 'Gaji - Penjualan',
            'description' => 'Gaji yang dibayarkan kepada personel penjualan.',
        ],
        'advertising_expenses' => [
            'name' => 'Beban Iklan',
            'description' => 'Biaya yang dikeluarkan untuk iklan dan promosi.',
        ],
        'administrative_and_general_expenses' => [
            'name' => 'Beban Administrasi dan Umum',
        ],
        'salaries_admin' => [
            'name' => 'Gaji - Administrasi',
            'description' => 'Gaji yang dibayarkan kepada personel administrasi.',
        ],
        'rent_expense' => [
            'name' => 'Beban Sewa',
            'description' => 'Biaya sewa ruang kantor atau toko.',
        ],
        'utility_expenses' => [
            'name' => 'Beban Utilitas',
            'description' => 'Biaya listrik, air, dan internet.',
        ],
        'depreciation_expense' => [
            'name' => 'Beban Penyusutan',
            'description' => 'Beban yang diakui untuk keausan aset tetap.',
        ],
    ],
];