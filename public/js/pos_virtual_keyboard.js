(function($) {
    'use strict';

    var keyboard = $('#pos_virtual_keyboard');
    var activeInput = null;
    var themeStorageKey = 'pos_virtual_keyboard_theme';

    if (!keyboard.length) {
        return;
    }

    applyTheme(getSavedTheme());

    function canUseKeyboard(element) {
        var input = $(element);

        if (!input.is('input[type="text"], input[type="search"], input[type="number"], input[type="tel"], input:not([type]), textarea')) {
            return false;
        }

        return !input.is(':disabled, [readonly], [type="hidden"], [type="password"], .no-virtual-keyboard');
    }

    function showKeyboard(input) {
        activeInput = input;
        keyboard.addClass('active').attr('aria-hidden', 'false');
        $('body').addClass('pos-virtual-keyboard-open');
        setLayout($(input).hasClass('input_number') || input.type === 'number' ? 'numeric' : 'text');
    }

    function hideKeyboard() {
        keyboard.removeClass('active').attr('aria-hidden', 'true');
        $('body').removeClass('pos-virtual-keyboard-open');
    }

    function getSavedTheme() {
        try {
            return localStorage.getItem(themeStorageKey) === 'dark' ? 'dark' : 'light';
        } catch (e) {
            return 'light';
        }
    }

    function saveTheme(theme) {
        try {
            localStorage.setItem(themeStorageKey, theme);
        } catch (e) {
            return;
        }
    }

    function applyTheme(theme) {
        var isDark = theme === 'dark';

        keyboard.toggleClass('is-dark', isDark);
        keyboard.find('[data-vk-action="theme"]')
            .attr('aria-label', isDark ? 'Use light keyboard theme' : 'Use dark keyboard theme')
            .html(isDark ? '<i class="fa fa-sun"></i>' : '<i class="fa fa-moon"></i>');
    }

    function toggleTheme() {
        var nextTheme = keyboard.hasClass('is-dark') ? 'light' : 'dark';

        applyTheme(nextTheme);
        saveTheme(nextTheme);
    }

    function setLayout(layout) {
        keyboard.find('[data-vk-layout]').removeClass('active');
        keyboard.find('[data-vk-layout="' + layout + '"]').addClass('active');
        keyboard.find('.pos-virtual-keyboard__layout').removeClass('active');
        keyboard.find('[data-layout="' + layout + '"]').addClass('active');
    }

    function setValue(input, value) {
        var start = getSelectionStart(input);
        var end = getSelectionEnd(input);
        var oldValue = input.value;

        if (typeof start !== 'number' || typeof end !== 'number') {
            input.value += value;
            start = input.value.length;
        } else {
            input.value = oldValue.substring(0, start) + value + oldValue.substring(end);
            start += value.length;
        }

        input.focus();
        setSelection(input, start, start);
        $(input).trigger('input').trigger('change');
    }

    function backspace(input) {
        var start = getSelectionStart(input);
        var end = getSelectionEnd(input);

        if (typeof start !== 'number' || typeof end !== 'number') {
            input.value = input.value.slice(0, -1);
        } else if (start !== end) {
            input.value = input.value.substring(0, start) + input.value.substring(end);
            setSelection(input, start, start);
        } else if (start > 0) {
            input.value = input.value.substring(0, start - 1) + input.value.substring(end);
            setSelection(input, start - 1, start - 1);
        }

        input.focus();
        $(input).trigger('input').trigger('change');
    }

    function getSelectionStart(input) {
        try {
            return input.selectionStart;
        } catch (e) {
            return null;
        }
    }

    function getSelectionEnd(input) {
        try {
            return input.selectionEnd;
        } catch (e) {
            return null;
        }
    }

    function setSelection(input, start, end) {
        try {
            input.setSelectionRange(start, end);
        } catch (e) {
            return;
        }
    }

    $(document).on('focusin', 'input, textarea', function() {
        if (canUseKeyboard(this)) {
            showKeyboard(this);
        }
    });

    $(document).on('mousedown touchstart', function(e) {
        if ($(e.target).closest('#pos_virtual_keyboard, input, textarea, .select2-container, .modal').length) {
            return;
        }

        hideKeyboard();
    });

    keyboard.on('mousedown touchstart', function(e) {
        e.preventDefault();
    });

    keyboard.on('click', '[data-vk-layout]', function() {
        setLayout($(this).data('vk-layout'));
    });

    keyboard.on('click', '[data-vk-key], [data-vk-action]', function() {
        var button = $(this);
        var action = button.data('vk-action');

        if (action === 'hide') {
            hideKeyboard();
            return;
        }

        if (action === 'theme') {
            toggleTheme();
            return;
        }

        if (!activeInput || !canUseKeyboard(activeInput)) {
            return;
        }

        if (action === 'backspace') {
            backspace(activeInput);
        } else if (action === 'clear') {
            activeInput.value = '';
            activeInput.focus();
            $(activeInput).trigger('input').trigger('change');
        } else if (action === 'enter') {
            activeInput.focus();
            $(activeInput).trigger($.Event('keydown', { key: 'Enter', which: 13, keyCode: 13 }));
            $(activeInput).trigger($.Event('keyup', { key: 'Enter', which: 13, keyCode: 13 }));
        } else {
            setValue(activeInput, button.attr('data-vk-key'));
        }
    });
})(jQuery);
