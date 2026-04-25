export const tu_theme_get_template = function(name, data) {
	const templates = TU_Theme.handlebar_templates || {};
	if (!templates[name]) {
		return '';
	}

	return Handlebars.compile(templates[name])(data);
}
