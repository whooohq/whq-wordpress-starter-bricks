import { tu_theme_get_template } from "../../helpers/tu-get-template";
import { TUHTMLElement } from "../../helpers/tu-html-element";

/**
 * TUButton web-component.
 */
class TUButton extends TUHTMLElement {
	static observedAttributes = ["disabled", "icon-left", "icon-right", "variant", "size"];

	/**
	 * Constructor.
	 */
	constructor() {
		super();
	}

	/**
	 * Attribute changed callback.
	 *
	 * @param {string} name Name of the attribute.
	 * @param {string} oldValue Old value.
	 * @param {string} newValue New value.
	 */
	attributeChangedCallback(name, oldValue, newValue) {
		const innerButton = this.querySelector('button');

		if (innerButton) {
			innerButton.setAttribute(name, newValue);
		}
	}

	render() {
		const attributes = {
			innerContent: this.innerText.trim(),
			iconLeft: this.getAttribute("icon-left"),
			iconRight: this.getAttribute("icon-right"),
		};

		this.innerHTML = tu_theme_get_template(
			'button-templates-template',
			attributes
		);

		// Propagate all the attributes from this element to the inner button element.
		const innerButton = this.querySelector('button');

		if (innerButton) {
			for (const attr of this.attributes) {
				innerButton.setAttribute(attr.name, attr.value);
			}
		}
	}

	/**
	 * Connected callback.
	 */
	connectedCallback() {
		super.connectedCallback();
	}
}

customElements.define("tu-button", TUButton);