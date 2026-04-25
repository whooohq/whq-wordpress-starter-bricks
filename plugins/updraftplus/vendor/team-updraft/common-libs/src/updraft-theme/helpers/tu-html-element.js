export class TUHTMLElement extends HTMLElement {
	tuRenderObserver = new MutationObserver(() => {
		this.tuRenderObserver.disconnect();

		// If the component has render function then render it.
		if (this.render) {
			this.render();
		}
	});

	/**
	 * Disconnected callback.
	 */
	disconnectedCallback() {
		if (super.disconnectedCallback) {
			super.disconnectedCallback();
		}

		this.tuRenderObserver.disconnect();
	}

	/**
	 * Connected callback.
	 */
	connectedCallback() {
		if (super.connectedCallback) {
			super.connectedCallback();
		}

		// If child nodes are already rendered, then run the renderer.
		if (this.hasChildNodes() && this.render) {
			this.render();
		} else {
			// Else wait for any mutation of child.
			this.tuRenderObserver.observe(this, { subtree: true, childList: true, characterData: true });
		}
	}
}
