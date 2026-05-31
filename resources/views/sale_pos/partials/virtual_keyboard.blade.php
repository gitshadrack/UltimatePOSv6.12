<div class="pos-virtual-keyboard no-print" id="pos_virtual_keyboard" aria-hidden="true">
	<div class="pos-virtual-keyboard__bar">
		<div class="pos-virtual-keyboard__tabs" role="group">
			<button type="button" class="pos-virtual-keyboard__tab active" data-vk-layout="text">ABC</button>
			<button type="button" class="pos-virtual-keyboard__tab" data-vk-layout="numeric">123</button>
		</div>
		<div class="pos-virtual-keyboard__actions">
			<button type="button" class="pos-virtual-keyboard__theme" data-vk-action="theme" aria-label="Toggle keyboard theme">
				<i class="fa fa-moon"></i>
			</button>
			<button type="button" class="pos-virtual-keyboard__hide" data-vk-action="hide" aria-label="Hide keyboard">
				<i class="fa fa-chevron-down"></i>
			</button>
		</div>
	</div>
	<div class="pos-virtual-keyboard__layout pos-virtual-keyboard__layout--text active" data-layout="text">
		<div class="pos-virtual-keyboard__row">
			@foreach(str_split('1234567890') as $key)
				<button type="button" class="pos-virtual-key" data-vk-key="{{ $key }}">{{ $key }}</button>
			@endforeach
		</div>
		<div class="pos-virtual-keyboard__row">
			@foreach(str_split('QWERTYUIOP') as $key)
				<button type="button" class="pos-virtual-key" data-vk-key="{{ $key }}">{{ $key }}</button>
			@endforeach
		</div>
		<div class="pos-virtual-keyboard__row">
			@foreach(str_split('ASDFGHJKL') as $key)
				<button type="button" class="pos-virtual-key" data-vk-key="{{ $key }}">{{ $key }}</button>
			@endforeach
		</div>
		<div class="pos-virtual-keyboard__row">
			@foreach(str_split('ZXCVBNM') as $key)
				<button type="button" class="pos-virtual-key" data-vk-key="{{ $key }}">{{ $key }}</button>
			@endforeach
			<button type="button" class="pos-virtual-key pos-virtual-key--wide" data-vk-action="backspace">
				<i class="fa fa-backspace"></i>
			</button>
		</div>
		<div class="pos-virtual-keyboard__row">
			<button type="button" class="pos-virtual-key pos-virtual-key--wide" data-vk-action="clear">Clear</button>
			<button type="button" class="pos-virtual-key pos-virtual-key--space" data-vk-key=" ">Space</button>
			<button type="button" class="pos-virtual-key pos-virtual-key--wide" data-vk-action="enter">Enter</button>
		</div>
	</div>
	<div class="pos-virtual-keyboard__layout pos-virtual-keyboard__layout--numeric" data-layout="numeric">
		<div class="pos-virtual-keyboard__numpad">
			@foreach(['7', '8', '9', '4', '5', '6', '1', '2', '3', '0', '.', '00'] as $key)
				<button type="button" class="pos-virtual-key" data-vk-key="{{ $key }}">{{ $key }}</button>
			@endforeach
			<button type="button" class="pos-virtual-key" data-vk-action="backspace">
				<i class="fa fa-backspace"></i>
			</button>
			<button type="button" class="pos-virtual-key" data-vk-action="clear">Clear</button>
			<button type="button" class="pos-virtual-key pos-virtual-key--enter" data-vk-action="enter">Enter</button>
		</div>
	</div>
</div>

<style>
	.pos-virtual-keyboard {
		backdrop-filter: blur(22px) saturate(1.4);
		background: rgba(238, 242, 247, 0.9);
		border: 1px solid rgba(255, 255, 255, 0.72);
		border-bottom: 0;
		border-radius: 18px 18px 0 0;
		bottom: 0;
		box-shadow: 0 -20px 48px rgba(15, 23, 42, 0.22);
		display: none;
		left: 0;
		margin: 0 auto;
		max-width: 1180px;
		padding: 12px;
		position: fixed;
		right: 0;
		z-index: 2050;
	}

	@supports not ((backdrop-filter: blur(22px)) or (-webkit-backdrop-filter: blur(22px))) {
		.pos-virtual-keyboard {
			background: #eef2f7;
		}
	}

	.pos-virtual-keyboard.active {
		display: block;
	}

	.pos-virtual-keyboard__bar {
		align-items: center;
		display: flex;
		justify-content: space-between;
		margin-bottom: 10px;
		min-height: 34px;
	}

	.pos-virtual-keyboard__tabs {
		background: rgba(255, 255, 255, 0.62);
		border: 1px solid rgba(148, 163, 184, 0.32);
		border-radius: 999px;
		display: inline-flex;
		gap: 3px;
		padding: 3px;
	}

	.pos-virtual-keyboard__tab,
	.pos-virtual-keyboard__hide,
	.pos-virtual-keyboard__theme {
		background: transparent;
		border: 0;
		color: #334155;
		font-size: 13px;
		font-weight: 700;
		height: 30px;
		line-height: 1;
		min-width: 46px;
		padding: 0 12px;
		touch-action: manipulation;
	}

	.pos-virtual-keyboard__tab {
		border-radius: 999px;
	}

	.pos-virtual-keyboard__tab.active {
		background: #ffffff;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
		color: #111827;
	}

	.pos-virtual-keyboard__actions {
		align-items: center;
		display: inline-flex;
		gap: 6px;
	}

	.pos-virtual-keyboard__hide,
	.pos-virtual-keyboard__theme {
		align-items: center;
		background: rgba(255, 255, 255, 0.74);
		border: 1px solid rgba(148, 163, 184, 0.34);
		border-radius: 999px;
		display: inline-flex;
		justify-content: center;
		min-width: 38px;
		padding: 0;
	}

	.pos-virtual-keyboard.is-dark {
		background: rgba(17, 24, 39, 0.9);
		border-color: rgba(255, 255, 255, 0.14);
		box-shadow: 0 -20px 52px rgba(0, 0, 0, 0.35);
	}

	.pos-virtual-keyboard.is-dark .pos-virtual-keyboard__tabs,
	.pos-virtual-keyboard.is-dark .pos-virtual-keyboard__hide,
	.pos-virtual-keyboard.is-dark .pos-virtual-keyboard__theme {
		background: rgba(31, 41, 55, 0.82);
		border-color: rgba(148, 163, 184, 0.22);
		color: #e5e7eb;
	}

	.pos-virtual-keyboard.is-dark .pos-virtual-keyboard__tab {
		color: #cbd5e1;
	}

	.pos-virtual-keyboard.is-dark .pos-virtual-keyboard__tab.active {
		background: rgba(55, 65, 81, 0.95);
		box-shadow: 0 1px 5px rgba(0, 0, 0, 0.28);
		color: #ffffff;
	}

	.pos-virtual-keyboard.is-dark .pos-virtual-key {
		background: rgba(55, 65, 81, 0.94);
		border-color: rgba(148, 163, 184, 0.18);
		box-shadow: 0 1px 1px rgba(0, 0, 0, 0.22), 0 4px 12px rgba(0, 0, 0, 0.18);
		color: #f8fafc;
	}

	.pos-virtual-keyboard.is-dark .pos-virtual-key:hover,
	.pos-virtual-keyboard.is-dark .pos-virtual-key:active {
		background: #475569;
	}

	.pos-virtual-keyboard.is-dark .pos-virtual-key--enter {
		background: #3b82f6;
		border-color: rgba(96, 165, 250, 0.55);
		box-shadow: 0 4px 14px rgba(59, 130, 246, 0.32);
	}

	.pos-virtual-keyboard.is-dark .pos-virtual-key--enter:hover,
	.pos-virtual-keyboard.is-dark .pos-virtual-key--enter:active {
		background: #2563eb;
	}

	.pos-virtual-keyboard__layout {
		display: none;
	}

	.pos-virtual-keyboard__layout.active {
		display: block;
	}

	.pos-virtual-keyboard__row {
		display: grid;
		gap: 8px;
		grid-template-columns: repeat(10, minmax(0, 1fr));
		margin-bottom: 8px;
	}

	.pos-virtual-keyboard__numpad {
		display: grid;
		gap: 8px;
		grid-template-columns: repeat(3, minmax(72px, 1fr));
		max-width: 520px;
	}

	.pos-virtual-key {
		background: rgba(255, 255, 255, 0.92);
		border: 1px solid rgba(148, 163, 184, 0.28);
		border-radius: 10px;
		box-shadow: 0 1px 1px rgba(15, 23, 42, 0.08), 0 4px 10px rgba(15, 23, 42, 0.05);
		color: #0f172a;
		font-size: 18px;
		font-weight: 600;
		min-height: 50px;
		padding: 8px;
		touch-action: manipulation;
		transition: background-color 80ms ease, box-shadow 80ms ease, transform 80ms ease;
	}

	.pos-virtual-key:hover,
	.pos-virtual-key:active {
		background: #f8fafc;
	}

	.pos-virtual-key:active {
		box-shadow: inset 0 2px 5px rgba(15, 23, 42, 0.12);
		transform: translateY(1px);
	}

	.pos-virtual-key--wide {
		grid-column: span 2;
	}

	.pos-virtual-key--space {
		grid-column: span 6;
	}

	.pos-virtual-key--enter {
		background: #2563eb;
		border-color: rgba(37, 99, 235, 0.6);
		box-shadow: 0 4px 12px rgba(37, 99, 235, 0.28);
		color: #ffffff;
	}

	.pos-virtual-key--enter:hover,
	.pos-virtual-key--enter:active {
		background: #1d4ed8;
	}

	body.pos-virtual-keyboard-open #scrollable-container {
		padding-bottom: 270px;
	}

	@media (max-width: 767px) {
		.pos-virtual-keyboard {
			border-radius: 16px 16px 0 0;
			padding: 10px 8px;
		}

		.pos-virtual-keyboard__row {
			gap: 5px;
			margin-bottom: 5px;
		}

		.pos-virtual-key {
			font-size: 15px;
			min-height: 44px;
			padding: 6px 4px;
		}

		body.pos-virtual-keyboard-open #scrollable-container {
			padding-bottom: 292px;
		}
	}
</style>
