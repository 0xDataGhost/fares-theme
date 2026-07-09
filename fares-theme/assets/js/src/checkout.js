/**
 * Checkout enhancements — progressively wraps the billing phone input
 * with intl-tel-input so the customer picks a country code from a flag
 * dropdown. Works with both the classic shortcode checkout and the
 * Blocks checkout (where the field is registered via the additional
 * checkout fields API and renders after React mount).
 */

import intlTelInput from "intl-tel-input/intlTelInputWithUtils";

const ENHANCED = new WeakSet();

// React (Blocks checkout) tracks controlled inputs through the value
// property descriptor — a plain `.value =` assignment doesn't reach
// its onChange. Route writes through the native setter + input event
// so Blocks state stays in sync with what the user sees.
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
		countryOrder: ["sa", "ae", "eg", "kw", "qa", "bh", "om"],
		// One value in play: the input holds the full +E.164 form. No
		// separate chip / hidden shadow input — those fought React's
		// controlled state and cleared the field on re-render.
		separateDialCode: false,
		nationalMode: false,
		strictMode: true,
		formatOnDisplay: false,
		// As-you-type spaces landed verbatim in Blocks state and
		// failed the server's strict +E.164 check.
		formatAsYouType: false,
		countrySearch: true,
		autoPlaceholder: "polite",
	});
	input._iti = iti;

	// The dial code comes from the flag selector — the user never
	// types it. Seed it when the field is empty, prepend it when the
	// user typed a bare national number, and re-announce the widget's
	// own dial-code swap to React when the country changes.
	const applyDialCode = () => {
		const dial = iti.getSelectedCountryData().dialCode;
		if (!dial) {
			return;
		}
		const value = input.value.trim();
		if (!value) {
			reactSetValue(input, "+" + dial);
		} else if (!value.startsWith("+")) {
			// National entry like "0115928..." → strip the trunk zero
			// and mount it on the selected country's code.
			reactSetValue(input, "+" + dial + value.replace(/^0+/, ""));
		} else {
			// The widget swapped the old dial code inside the DOM value
			// itself — React didn't see that write, so replay it.
			reactSetValue(input, input.value);
		}
	};

	applyDialCode();
	input.addEventListener("countrychange", applyDialCode);

	// Whatever shape the user left in the field (national digits,
	// spaced, half-formatted), normalise to +E.164 on blur — utils
	// (bundled) parses against the selected country.
	input.addEventListener("blur", () => {
		const full = iti.getNumber();
		if (full && full !== input.value) {
			reactSetValue(input, full);
		}
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
