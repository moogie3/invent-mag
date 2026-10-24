<?php

/*
 * Shopee import category taxonomy — the 35 prefixes from the shop's
 * SKU_Stock_Guide.docx (926 products, prefix = category, e.g. TRS-0001).
 *
 * `entries` is ordered most-specific first: when several categories match a
 * product name (e.g. "Transistor IGBT Mosfet"), the earlier one wins.
 * Matching tiers in ShopeeCategoryResolver:
 *   1. SKU prefix  — "TRS-0001-A" → TRS (or an alias like FBT → FLY)
 *   2. Name keywords (English + Indonesian), tested in this order;
 *      keywords of 4 chars or less match whole words only, longer ones match
 *      as substrings ("kapasitor", "stop kontak", "step-down").
 *   3. Shopee leaf equal to an existing local category name.
 *   4. `fallback` — the guide's "Other Spare Part" catch-all.
 *
 * Categories are created lazily (firstOrCreate, per tenant) with
 * name = prefix, description = label; an existing category with the same
 * prefix/alias or description label is reused instead.
 */

return [
    'entries' => [
        ['prefix' => 'IGBT', 'label' => 'IGBT', 'aliases' => [], 'keywords' => ['igbt']],
        ['prefix' => 'MOS', 'label' => 'Mosfet', 'aliases' => [], 'keywords' => ['mosfet', 'mos fet']],
        ['prefix' => 'DIO', 'label' => 'Diode', 'aliases' => [], 'keywords' => ['dioda', 'diode', 'schottky', '1n400', '1n540', '1n581']],
        ['prefix' => 'FLY', 'label' => 'Flyback', 'aliases' => ['FBT'], 'keywords' => ['flyback', 'fbt', 'fly back']],
        ['prefix' => 'TRS', 'label' => 'Transistor', 'aliases' => ['TR'], 'keywords' => ['transistor', 'triac', 'darlington', 'tip41', 'tip42', 'tip45', 'tip47']],
        ['prefix' => 'NTC', 'label' => 'NTC', 'aliases' => [], 'keywords' => ['ntc', 'thermistor', 'termistor']],
        ['prefix' => 'FUS', 'label' => 'Fuse', 'aliases' => [], 'keywords' => ['fuse', 'sekring', 'thermofuse']],
        ['prefix' => 'RES', 'label' => 'Resistor', 'aliases' => [], 'keywords' => ['resistor', 'resistors', 'kapur']],
        ['prefix' => 'CAP', 'label' => 'Capacitor', 'aliases' => [], 'keywords' => ['kapasitor', 'capacitor', 'elco', 'mylar', 'milar', 'condensor']],
        ['prefix' => 'IC', 'label' => 'Integrated Circuit (IC)', 'aliases' => [], 'keywords' => ['ic', 'integrated circuit']],
        ['prefix' => 'TRF', 'label' => 'Transformator', 'aliases' => [], 'keywords' => ['transformator', 'transformer', 'trafo']],
        ['prefix' => 'DWM', 'label' => 'Washing Machine Dynamo', 'aliases' => [], 'keywords' => ['dinamo mesin cuci', 'dynamo mesin cuci', 'motor mesin cuci', 'wash motor', 'spin motor']],
        ['prefix' => 'DFN', 'label' => 'Fan Dynamo', 'aliases' => [], 'keywords' => ['dinamo kipas', 'dynamo kipas', 'kipas', 'fan dynamo', 'fan motor', 'blender motor', 'dinamo blender']],
        ['prefix' => 'WMP', 'label' => 'Washing Machine Spare Part', 'aliases' => [], 'keywords' => ['mesin cuci', 'washing machine', 'pulsator', 'gearbox']],
        ['prefix' => 'LED', 'label' => 'LED Light', 'aliases' => [], 'keywords' => ['led']],
        ['prefix' => 'LMP', 'label' => 'Lamp', 'aliases' => [], 'keywords' => ['lamp', 'lampu', 'bohlam', 'dimmer']],
        ['prefix' => 'SWT', 'label' => 'Switch', 'aliases' => [], 'keywords' => ['switch', 'saklar', 'push button', 'rocker']],
        ['prefix' => 'BAT', 'label' => 'Battery', 'aliases' => [], 'keywords' => ['battery', 'baterai', 'aki']],
        ['prefix' => 'RMT', 'label' => 'Remote Control', 'aliases' => [], 'keywords' => ['remote', 'remot']],
        ['prefix' => 'CON', 'label' => 'Connector', 'aliases' => [], 'keywords' => ['connector', 'jack', 'rca', 'bnc', 'xlr']],
        ['prefix' => 'SOC', 'label' => 'Electric Socket', 'aliases' => [], 'keywords' => ['socket', 'soket', 'stop kontak', 'colokan', 'outlet']],
        ['prefix' => 'CBL', 'label' => 'Cable', 'aliases' => [], 'keywords' => ['cable', 'kabel']],
        ['prefix' => 'TL', 'label' => 'Hand Tool', 'aliases' => [], 'keywords' => ['tang', 'obeng', 'solder', 'pliers', 'tweezer', 'hand tool', 'kunci']],
        ['prefix' => 'MTR', 'label' => 'Multitester', 'aliases' => [], 'keywords' => ['multitester', 'multimeter', 'avometer']],
        ['prefix' => 'HTE', 'label' => 'Heating Element', 'aliases' => [], 'keywords' => ['heating element', 'heater', 'pemanas', 'elemen']],
        ['prefix' => 'GER', 'label' => 'Gear/Mechanical Part', 'aliases' => [], 'keywords' => ['gear', 'gigi']],
        ['prefix' => 'ADP', 'label' => 'Adaptor', 'aliases' => [], 'keywords' => ['adaptor', 'adapter', 'power supply', 'psu']],
        ['prefix' => 'EKT', 'label' => 'Electronic Small Kit', 'aliases' => [], 'keywords' => ['electronic kit', 'diy kit', 'inverter kit', 'step-up', 'step-down', 'step up', 'step down']],
        ['prefix' => 'PAK', 'label' => 'Power Amplifier Kit', 'aliases' => [], 'keywords' => ['power amplifier', 'amplifier kit', 'amplifier', 'ocl']],
        ['prefix' => 'OVL', 'label' => 'Water Pump Overload', 'aliases' => [], 'keywords' => ['overload', 'over load']],
        ['prefix' => 'RLY', 'label' => 'Refrigerator Relay', 'aliases' => [], 'keywords' => ['relay', 'defrost']],
        ['prefix' => 'THM', 'label' => 'Thermostat', 'aliases' => [], 'keywords' => ['thermostat', 'bimetal']],
        ['prefix' => 'MCB', 'label' => 'Circuit Breaker', 'aliases' => [], 'keywords' => ['mcb', 'circuit breaker']],
        ['prefix' => 'GLU', 'label' => 'Glue/Adhesive/Consumable', 'aliases' => [], 'keywords' => ['glue', 'lem', 'glue gun', 'selotip', 'insulation tape']],
        ['prefix' => 'MISC', 'label' => 'Other Spare Part', 'aliases' => [], 'keywords' => []],
    ],

    // Last-resort category when neither the SKU prefix nor the name matches.
    'fallback' => 'MISC',
];
