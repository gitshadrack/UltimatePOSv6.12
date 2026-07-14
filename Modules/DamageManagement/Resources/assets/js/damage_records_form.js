/* global __read_number, __write_number, __currency_convert_recursively, LANG */

function initDamageRecordsTable(options) {
    var settings = $.extend(
        {
            searchUrl: null,
            detailsUrl: null,
            basisSelect: '#damage_compensation_basis'
        },
        options || {}
    );

    var $table = $('#damage_products_table');
    var $tbody = $table.find('tbody');
    var rowTemplate = $('#damage_product_row_template').html();
    var rowIndex = 0;
    var initialRows = settings.initialRows || [];

    function ensureEmptyRow() {
        if ($tbody.find('tr.damage-row').length === 0) {
            if ($tbody.find('.empty-row').length === 0) {
                $tbody.append(
                    '<tr class="empty-row"><td colspan="7" class="text-center text-muted">' +
                        LANG.damage_no_records_selected +
                        '</td></tr>'
                );
            }
        } else {
            $tbody.find('.empty-row').remove();
        }
    }

    function fetchVariationDetails(variationId, onSuccess) {
        if (!settings.detailsUrl) {
            return;
        }
        var url = settings.detailsUrl.replace('__id__', variationId);
        $.ajax({
            method: 'GET',
            url: url,
            success: function (data) {
                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                }
            },
            error: function () {
                toastr.error(LANG.something_went_wrong || 'Something went wrong');
            }
        });
    }

    function updateRowTotals($row) {
        var qty = __read_number($row.find('.damage-quantity'));
        if (isNaN(qty)) {
            qty = 0;
        }
        var unitPurchase = __read_number($row.find('.damage-unit-purchase'));
        if (isNaN(unitPurchase)) {
            unitPurchase = 0;
        }
        var unitSell = __read_number($row.find('.damage-unit-sell'));
        if (isNaN(unitSell)) {
            unitSell = 0;
        }
        var compensation = __read_number($row.find('.damage-compensation'));
        if (isNaN(compensation)) {
            compensation = 0;
        }

        var basis = $(settings.basisSelect).val();
        if (basis === 'purchase') {
            compensation = qty * unitPurchase;
            __write_number($row.find('.damage-compensation'), compensation);
        } else if (basis === 'sell') {
            compensation = qty * unitSell;
            __write_number($row.find('.damage-compensation'), compensation);
        }

        $row.find('.damage-row-purchase').text(qty * unitPurchase);
        $row.find('.damage-row-sell').text(qty * unitSell);
        $row.find('.damage-row-comp').text(compensation);

        updateOverallTotals();
    }

    function updateOverallTotals() {
        var totalPurchase = 0;
        var totalSell = 0;
        var totalComp = 0;

        $tbody.find('tr.damage-row').each(function () {
            var $row = $(this);
            totalPurchase += parseFloat($row.find('.damage-row-purchase').text()) || 0;
            totalSell += parseFloat($row.find('.damage-row-sell').text()) || 0;
            totalComp += parseFloat($row.find('.damage-row-comp').text()) || 0;
        });

        $('#damage_total_purchase_display').text(totalPurchase);
        $('#damage_total_sell_display').text(totalSell);
        $('#damage_total_compensation_display').text(totalComp);

        __currency_convert_recursively($('#damage_products_table').closest('.box-body'));
    }

    function registerRowEvents($row) {
        var $select = $row.find('.damage-variation-select');

        if (settings.searchUrl) {
            $select.select2({
                ajax: {
                    url: settings.searchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { term: params.term };
                    },
                    processResults: function (data) {
                        return data;
                    }
                },
                placeholder: $select.data('placeholder') || '',
                width: '100%'
            });
        } else {
            $select.select2({ placeholder: $select.data('placeholder') || '', width: '100%' });
        }

        $select.on('select2:select', function (e) {
            var variationId = e.params.data.id;
            fetchVariationDetails(variationId, function (details) {
                applyVariationDetails($row, details);
            });
        });

        $row.on('change input', '.damage-quantity, .damage-unit-purchase, .damage-unit-sell, .damage-compensation', function () {
            updateRowTotals($row);
        });

        $row.find('.remove-damage-row').on('click', function () {
            $row.remove();
            ensureEmptyRow();
            updateOverallTotals();
        });
    }

    function applyVariationDetails($row, details) {
        if (!details) {
            return;
        }

        var $select = $row.find('.damage-variation-select');

        var existingOption = $select.find("option[value='" + details.variation_id + "']");
        if (existingOption.length === 0) {
            var option = new Option(details.product_name, details.variation_id, true, true);
            $select.append(option);
        }
        $select.val(details.variation_id).trigger('change.select2');

        $row.find('.damage-product-id').val(details.product_id || '');
        __write_number($row.find('.damage-unit-purchase'), details.default_purchase_price || 0);
        __write_number($row.find('.damage-unit-sell'), details.default_sell_price || 0);

        $row.find('.damage-brand-text').text(details.brand_name || '-');
        $row.find('.damage-category-text').text(details.category_name || '-');
        $row.find('.damage-unit-text').text(details.unit_name || '-');

        $row.find('.damage-quantity').attr('data-quantity-precision', details.quantity_precision || 2);

        updateRowTotals($row);
    }

    function addRow(initialData) {
        ensureEmptyRow();

        var html = rowTemplate.replace(/__index__/g, rowIndex);
        rowIndex++;

        var $row = $(html);
        $tbody.append($row);

        registerRowEvents($row);

        if (initialData) {
            applyVariationDetails($row, initialData);
            if (initialData.quantity) {
                __write_number($row.find('.damage-quantity'), initialData.quantity);
            }
            if (initialData.unit_purchase_price) {
                __write_number($row.find('.damage-unit-purchase'), initialData.unit_purchase_price);
            }
            if (initialData.unit_sell_price) {
                __write_number($row.find('.damage-unit-sell'), initialData.unit_sell_price);
            }
            if (initialData.expected_compensation) {
                __write_number($row.find('.damage-compensation'), initialData.expected_compensation);
            }
        }

        updateRowTotals($row);
    }

    $('#add_damage_product_row').on('click', function () {
        addRow();
    });

    $(settings.basisSelect).on('change', function () {
        $tbody.find('tr.damage-row').each(function () {
            updateRowTotals($(this));
        });
    });

    $('form#damage_record_form').on('submit', function (e) {
        if ($tbody.find('tr.damage-row').length === 0) {
            e.preventDefault();
            toastr.error(LANG.damage_no_records_selected);
            return false;
        }
        $tbody.find('tr.damage-row').each(function () {
            updateRowTotals($(this));
        });
        return true;
    });

    if (initialRows.length) {
        initialRows.forEach(function (rowData) {
            addRow(rowData);
        });
    } else {
        addRow();
    }
    
    // Initialize global product search with Select2
    var globalSearchUrl = settings.searchUrl;
    var globalSearchDetailsUrl = settings.detailsUrl;
    
    // Store selected products in array
    var globalSelectedProducts = {};
    
    // Initialize Select2 for global search
    var $globalSearch = $('<select id="global_damage_product_select" style="width:100%"></select>');
    $('#global_damage_product_search').replaceWith($globalSearch);
    
    $globalSearch.select2({
        ajax: {
            url: globalSearchUrl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { term: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.results.map(function(item) {
                        return {
                            id: item.id,
                            text: item.text,
                            product_id: item.product_id || item.id
                        };
                    })
                };
            },
            cache: true
        },
        placeholder: $globalSearch.data('placeholder') || 'Enter Product name / SKU / Scan bar code',
        minimumInputLength: 2,
        allowClear: true,
        width: '100%'
    });
    
    // When a product is selected, add it to the table
    $globalSearch.on('select2:select', function(e) {
        var data = e.params.data;
        var variationId = data.id;
        
        // Check if product already exists in the table
        var exists = false;
        $tbody.find('tr.damage-row').each(function() {
            var $select = $(this).find('.damage-variation-select');
            if ($select.val() == variationId) {
                exists = true;
                return false;
            }
        });
        
        if (exists) {
            toastr.warning('Product already added');
            $globalSearch.val(null).trigger('change');
            return;
        }
        
        // Fetch full product details and add to table
        fetchVariationDetails(variationId, function(details) {
            if (details) {
                addRow(details);
                $globalSearch.val(null).trigger('change');
            }
        });
    });
    
    // Add product button click handler
    $('#add_product_from_search').on('click', function() {
        var selectedValue = $globalSearch.val();
        if (!selectedValue) {
            return;
        }
        
        $globalSearch.trigger('select2:select');
    });
    
    // Allow Enter key to add product
    $globalSearch.on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            var $activeElement = $(document.activeElement);
            if ($activeElement.hasClass('select2-search__field')) {
                $('#add_product_from_search').click();
            }
        }
    });
}

function initDamageContactSelects(selector) {
    var endpoint = '/damage-records/contacts';

    $(selector).each(function () {
        var $el = $(this);
        var type = $el.data('contact-type') || 'customer';
        var placeholder = $el.data('placeholder') || '';
        var initialId = $el.data('initial-id');
        var initialText = $el.data('initial-text');

        $el.select2({
            ajax: {
                url: endpoint,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        term: params.term,
                        type: type,
                    };
                },
                processResults: function (data) {
                    return data;
                }
            },
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            minimumInputLength: 1
        });

        if (initialId && initialText) {
            var option = new Option(initialText, initialId, true, true);
            $el.append(option).trigger('change');
        }
    });
}
