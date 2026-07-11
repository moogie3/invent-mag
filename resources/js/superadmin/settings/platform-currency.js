document.addEventListener('DOMContentLoaded', function () {
    var currencyCode = document.getElementById('currencyCode');
    var currencySymbol = document.getElementById('currencySymbol');
    var decimalSep = document.getElementById('decimalSeparator');
    var thousandSep = document.getElementById('thousandSeparator');
    var decimalPlaces = document.getElementById('decimalPlaces');

    var presets = {
        'USD': { symbol: '$', decimal: '.', thousand: ',', places: 2 },
        'IDR': { symbol: 'Rp', decimal: ',', thousand: '.', places: 0 },
        'EUR': { symbol: '€', decimal: ',', thousand: '.', places: 2 },
        'GBP': { symbol: '£', decimal: '.', thousand: ',', places: 2 },
        'JPY': { symbol: '¥', decimal: '.', thousand: ',', places: 0 },
        'SGD': { symbol: 'S$', decimal: '.', thousand: ',', places: 2 },
        'MYR': { symbol: 'RM', decimal: '.', thousand: ',', places: 2 },
        'PHP': { symbol: '₱', decimal: '.', thousand: ',', places: 2 },
        'THB': { symbol: '฿', decimal: '.', thousand: ',', places: 2 },
        'VND': { symbol: '₫', decimal: '.', thousand: ',', places: 0 },
        'KRW': { symbol: '₩', decimal: '.', thousand: ',', places: 0 },
        'CNY': { symbol: '¥', decimal: '.', thousand: ',', places: 2 },
        'INR': { symbol: '₹', decimal: '.', thousand: ',', places: 2 },
        'AUD': { symbol: 'A$', decimal: '.', thousand: ',', places: 2 },
        'CAD': { symbol: 'C$', decimal: '.', thousand: ',', places: 2 },
        'CHF': { symbol: 'Fr', decimal: '.', thousand: ',', places: 2 },
        'SAR': { symbol: '﷼', decimal: '.', thousand: ',', places: 2 },
        'NZD': { symbol: 'NZ$', decimal: '.', thousand: ',', places: 2 },
        'HKD': { symbol: 'HK$', decimal: '.', thousand: ',', places: 2 },
    };

    if (currencyCode) {
        currencyCode.addEventListener('change', function () {
            var code = this.value;
            var preset = presets[code];
            if (preset) {
                if (currencySymbol) currencySymbol.value = preset.symbol;
                if (decimalSep) decimalSep.value = preset.decimal;
                if (thousandSep) thousandSep.value = preset.thousand;
                if (decimalPlaces) decimalPlaces.value = preset.places;
            }
        });
    }
});
