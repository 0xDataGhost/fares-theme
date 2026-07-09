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

	intlTelInput(input, {
		initialCountry: "sa",
		countryOrder: ["sa", "ae", "eg", "kw", "qa", "bh", "om"],
		// Store and display the full +E.164 form directly. That way
		// there is only one value in play — no cross-parsing between
		// a visible national number and a hidden international shadow,
		// which was fighting React's controlled state in Blocks
		// checkout and clearing the field on blur.
		separateDialCode: false,
		nationalMode: false,
		// v25 `strictMode` blocks invalid characters and keeps the
		// dial-code prefix locked, so backspacing "+966" is disallowed.
		// The library's ensureHasDialCode behaviour auto-inserts the
		// code when a country is picked — the user types digits only.
		strictMode: true,
		formatOnDisplay: false,
		// As-you-type formatting injects spaces ("+20 10 0123 4567")
		// which then land verbatim in Blocks state and fail the
		// server's strict +E.164 check. Keep the value digits-only.
		formatAsYouType: false,
		countrySearch: true,
		autoPlaceholder: "polite",
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
