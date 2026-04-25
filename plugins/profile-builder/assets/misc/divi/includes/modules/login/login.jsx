// External Dependencies
import React from "react";
import AjaxComponent from "./../base/AjaxComponent/AjaxComponent";

// Internal Dependencies
import "./style.css";

class Login extends AjaxComponent {
  static slug = "wppb_login";

  _shouldReload(oldProps, newProps) {
    return (
      oldProps.register_url !== newProps.register_url ||
      oldProps.lostpassword_url !== newProps.lostpassword_url ||
      oldProps.redirect_url !== newProps.redirect_url ||
      oldProps.logout_redirect_url !== newProps.logout_redirect_url ||
      oldProps.toggle_ajax_validation !== newProps.toggle_ajax_validation ||
      oldProps.toggle_auth_field !== newProps.toggle_auth_field
    );
  }

  _reloadFormData(props) {
    var formData = new FormData();

    formData.append("action", "wppb_divi_extension_ajax");
    formData.append("form_type", "l");
    formData.append("register_url", props.register_url);
    formData.append("lostpassword_url", props.lostpassword_url);
    formData.append("redirect_url", props.redirect_url);
    formData.append("logout_redirect_url", props.logout_redirect_url);
    formData.append("toggle_ajax_validation", props.toggle_ajax_validation);
    formData.append("toggle_auth_field", props.toggle_auth_field);

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

export default Login;
