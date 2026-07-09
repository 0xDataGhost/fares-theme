/**
 * Checkout enhancements — progressively wraps the billing phone input
 * with intl-tel-input so the customer picks a country code from a flag
 * dropdown. Works with both the classic shortcode checkout and the
 * Blocks checkout (where the field is registered via the additional
 * checkout fields API and renders after React mount).
 */

import intlTelInput from "intl-tel-input/intlTelInputWithUtils";

const ENHANCED = new WeakSet();

function enhance(input) {
	if (ENHANCED.has(input)) {
		return;
	}
	ENHANCED.add(input);

	const iti = intlTelInput(input, {
		initialCountry: "sa",
		preferredCountries: ["sa", "ae", "eg", "kw", "qa", "bh", "om"],
		separateDialCode: false,
		nationalMode: false,
		formatOnDisplay: true,
		countrySearch: true,
		// Server-side always sees the international form.
		hiddenInput: () => ({
			phone: input.dataset.faresIntlHidden || "",
		}),
	});

	// Store the widget on the input so future submit handlers can reach
	// it (e.g. Blocks checkout re-serializing on every re-render).
	input._iti = iti;

	// Normalise the visible value to full international format at submit.
	const form = input.closest("form");
	if (form) {
		form.addEventListener(
			"submit",
			() => {
				if (iti.isValidNumber()) {
					input.value = iti.getNumber();
				}
			},
			true
		);
	}

	// Keep react-controlled inputs (Blocks) in sync — dispatch a native
	// input event whenever the country changes so React sees the update.
	input.addEventListener("countrychange", () => {
		input.dispatchEvent(new Event("input", { bubbles: true }));
	});
}

function scan(root) {
	(root || document).querySelectorAll("input[data-fares-intl-tel]").forEach(enhance);
}

function init() {
	scan(document);

	// Blocks checkout renders after DOMContentLoaded; watch for late mounts.
	const observer = new MutationObserver((mutations) => {
		for (const m of mutations) {
			for (const node of m.addedNodes) {
				if (node.nodeType === 1) {
					if (node.matches && node.matches("input[data-fares-intl-tel]")) {
						enhance(node);
					} else if (node.querySelectorAll) {
						scan(node);
					}
				}
			}
		}
	});
	observer.observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", init);
} else {
	init();
}
