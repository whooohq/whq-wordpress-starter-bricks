// External Dependencies
import React from "react";
import AjaxComponent from "./../base/AjaxComponent/AjaxComponent";

// Internal Dependencies
import "./style.css";

class EditProfile extends AjaxComponent {
  static slug = "wppb_edit_profile";

  _shouldReload(oldProps, newProps) {
    return (
      oldProps.form_name !== newProps.form_name ||
      oldProps.redirect_url !== newProps.redirect_url ||
      oldProps.toggle_ajax_validation !== newProps.toggle_ajax_validation
    );
  }

  _reloadFormData(props) {
    var formData = new FormData();

    formData.append("action", "wppb_divi_extension_ajax");
    formData.append("form_type", "epf");
    formData.append("form_name", props.form_name);
    formData.append("redirect_url", props.redirect_url);
    formData.append("toggle_ajax_validation", props.toggle_ajax_validation);

    return formData;
  }

  render() {
    return super.render();
  }

  _render() {
    // console.log("_render");
    return (
      <div
        className="wppb-form-container"
        dangerouslySetInnerHTML={{ __html: this.state.result }}
      />
    );
  }
}

export default EditProfile;
