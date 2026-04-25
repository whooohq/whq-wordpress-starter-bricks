// External Dependencies
import React from "react";
import AjaxComponent from "./../base/AjaxComponent/AjaxComponent";

// Internal Dependencies
import "./style.css";

class Register extends AjaxComponent {
  static slug = "wppb_register";

  _shouldReload(oldProps, newProps) {
    return (
      oldProps.form_name !== newProps.form_name ||
      oldProps.role !== newProps.role ||
      oldProps.toggle_automatic_login !== newProps.toggle_automatic_login ||
      oldProps.redirect_url !== newProps.redirect_url ||
      oldProps.logout_redirect_url !== newProps.logout_redirect_url ||
      oldProps.toggle_ajax_validation !== newProps.toggle_ajax_validation
    );
  }

  _reloadFormData(props) {
    var formData = new FormData();

    formData.append("action", "wppb_divi_extension_ajax");
    formData.append("form_type", "rf");
    formData.append("form_name", props.form_name);
    formData.append("role", props.role);
    formData.append("redirect_url", props.redirect_url);
    formData.append("logout_redirect_url", props.logout_redirect_url);
    formData.append("toggle_ajax_validation", props.toggle_ajax_validation);
    formData.append("toggle_automatic_login", props.toggle_automatic_login);

    return formData;
  }

  render() {
    return super.render();
  }

  _render() {
    return (
      <div
        className="wppb-form-container"
        dangerouslySetInnerHTML={{ __html: this.state.result }}
      />
    );
  }
}

export default Register;
