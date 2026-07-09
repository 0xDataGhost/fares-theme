/**
 * Checkout enhancements — progressively wraps the billing phone input
 * with intl-tel-input so the customer picks a country code from a flag
 * dropdown. Works with both the classic shortcode checkout and the
 * Blocks checkout (where the field is registered via the additional
 * checkout fields API and renders after React mount).
 */

import intlTelInput from "intl-tel-input/intlTelInputWithUtils";

const ENHANCED = new WeakSet();

// React (Blocks checkout) tracks controlled inputs via a private
// value setter — assigning to `.value` alone won't fire onChange. Use
// the prototype's descriptor so React sees the change and updates its
// state, keeping the phone value alive across re-renders.
const nativeValueSetter = Object.getOwnPropertyDescriptor(
	window.HTMLInputElement.prototype,
	"value"
).set;

function reactSetValue(input, value) {
	nativeValueSetter.call(input, value);
	input.dispatchEvent(new Event("input", { bubbles: true }));
}

function enhance(input) {
	if (ENHANCED.has(input)) {
		return;
	}
	ENHANCED.add(input);

	const iti = intlTelInput(input, {
		initialCountry: "sa",
		preferredCountries: ["sa", "ae", "eg", "kw", "qa", "bh", "om"],
		// Sticky flag+code chip. Users type only the national digits;
		// we bridge to +E.164 for React storage in the blur handler
		// below.
		separateDialCode: true,
		nationalMode: false,
		// Don't reformat on blur — the widget rewrites input.value to
		// its own spacing, which fights React's controlled state.
		formatOnDisplay: false,
		countrySearch: true,
	});

	// Store the widget on the input so future observers can reach it.
	input._iti = iti;

	// On blur / country change, hand React the +E.164 form so the
	// value that ends up in Blocks state (and in the additional-field
	// storage → billing_phone) is exactly what the server validates.
	const syncToReact = () => {
		if (!iti.isValidNumber()) {
			return;
		}
		const intl = iti.getNumber();
		if (intl && intl !== input.value) {
			reactSetValue(input, intl);
		}
	};

	input.addEventListener("blur", syncToReact);
	input.addEventListener("countrychange", syncToReact);
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
