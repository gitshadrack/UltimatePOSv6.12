/* global __read_number, __write_number, __currency_convert_recursively */

function initDamageRecordForm(options) {
    const config = Object.assign({
        variationSelect: '#damage_variation_id',
        productIdInput: '#damage_product_id',
        unitPurchaseField: '#damage_unit_purchase_price',
        unitSellField: '#damage_unit_sell_price',
        quantityInput: '#damage_quantity',
        basisSelect: '#damage_compensation_basis',
        compensationInput: '#damage_compensation_amount',
        purchaseDisplay: '#damage_total_purchase_display',
        sellDisplay: '#damage_total_sell_display',
        compensationDisplay: '#damage_total_compensation_display',
        brandDisplay: '#damage_brand_view',
        categoryDisplay: '#damage_category_view',
        unitDisplay: '#damage_unit_view',
        searchUrl: null,
        detailsUrl: null,
        initialVariation: null
    }, options || {});

    const variationSelect = $(config.variationSelect);
    const productIdInput = $(config.productIdInput);
    const unitPurchaseField = $(config.unitPurchaseField);
    const unitSellField = $(config.unitSellField);
    const quantityInput = $(config.quantityInput);
    const basisSelect = $(config.basisSelect);
    const compensationInput = $(config.compensationInput);
    const purchaseDisplay = $(config.purchaseDisplay);
    const sellDisplay = $(config.sellDisplay);
    const compensationDisplay = $(config.compensationDisplay);
    const brandDisplay = $(config.brandDisplay);
    const categoryDisplay = $(config.categoryDisplay);
    const unitDisplay = $(config.unitDisplay);

    variationSelect.select2({
        ajax: {
            url: config.searchUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return data;
            }
        },
        minimumInputLength: 1,
        placeholder: variationSelect.data('placeholder') || ''
    });

    variationSelect.on('select2:select', function (e) {
        const variationId = e.params.data.id;
        fetchVariationDetails(variationId);
    });

    quantityInput.on('input change', function () {
        updateTotals();
    });

    basisSelect.on('change', function () {
        compensationInput.data('manual', false);
        updateTotals();
    });

    compensationInput.on('input change', function () {
        $(this).data('manual', true);
        updateCompensationDisplay();
    });

    if (config.initialVariation) {
        applyVariationDetails(config.initialVariation, { skipSelectUpdate: true });
    } else {
        updateTotals();
    }

    function fetchVariationDetails(variationId) {
        if (!config.detailsUrl) {
            return;
        }

        const url = config.detailsUrl.replace('__id__', variationId);
        $.ajax({
            method: 'GET',
            url: url,
            success: function (data) {
                applyVariationDetails(data, { skipSelectUpdate: true });
            },
            error: function () {
                toastr.error(LANG.something_went_wrong || 'Something went wrong');
            }
        });
    }

    function applyVariationDetails(details, options = {}) {
        if (!details) {
            return;
        }

        if (!options.skipSelectUpdate) {
            const option = new Option(details.product_name, details.variation_id, true, true);
            variationSelect.append(option).trigger('change');
        }

        productIdInput.val(details.product_id || '');
        unitPurchaseField.val(details.default_purchase_price || 0);
        unitSellField.val(details.default_sell_price || 0);

        brandDisplay.text(details.brand_name || '-');
        categoryDisplay.text(details.category_name || '-');
        unitDisplay.text(details.unit_name || '-');

        quantityInput.attr('data-quantity-precision', details.quantity_precision || 2);

        compensationInput.data('manual', false);
        updateTotals();
    }

    function readQuantity() {
        let quantity = __read_number(quantityInput);
        if (isNaN(quantity)) {
            quantity = 0;
        }
        return quantity;
    }

    function updateTotals() {
        const quantity = readQuantity();
        const unitPurchase = parseFloat(unitPurchaseField.val()) || 0;
        const unitSell = parseFloat(unitSellField.val()) || 0;

        const totalPurchase = quantity * unitPurchase;
        const totalSell = quantity * unitSell;

        // Update display with currency formatting
        if (purchaseDisplay.find('.display_currency').length > 0) {
            purchaseDisplay.find('.display_currency').text(totalPurchase);
        } else {
            purchaseDisplay.html('<span class="display_currency" data-currency_symbol="true">' + totalPurchase + '</span>');
        }
        
        if (sellDisplay.find('.display_currency').length > 0) {
            sellDisplay.find('.display_currency').text(totalSell);
        } else {
            sellDisplay.html('<span class="display_currency" data-currency_symbol="true">' + totalSell + '</span>');
        }

        const basis = basisSelect.val();
        let compensationValue = parseFloat(__read_number(compensationInput)) || 0;

        if (basis === 'purchase') {
            compensationValue = totalPurchase;
            compensationInput.prop('readonly', true);
            compensationInput.data('manual', false);
        } else if (basis === 'sell') {
            compensationValue = totalSell;
            compensationInput.prop('readonly', true);
            compensationInput.data('manual', false);
        } else {
            compensationInput.prop('readonly', false);
            if (!compensationInput.data('manual')) {
                compensationValue = totalPurchase;
            }
        }

        __write_number(compensationInput, compensationValue);
        updateCompensationDisplay();
        __currency_convert_recursively($(document));
    }

    function updateCompensationDisplay() {
        const amount = __read_number(compensationInput);
        if (compensationDisplay.find('.display_currency').length > 0) {
            compensationDisplay.find('.display_currency').text(amount);
        } else {
            compensationDisplay.html('<span class="display_currency" data-currency_symbol="true">' + amount + '</span>');
        }
        __currency_convert_recursively($(document));
    }
}
