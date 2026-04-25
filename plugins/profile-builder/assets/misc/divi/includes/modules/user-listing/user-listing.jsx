// External Dependencies
import React from "react";
import AjaxComponent from "./../base/AjaxComponent/AjaxComponent";

// Internal Dependencies
import "./style.css";

class UserListing extends AjaxComponent {
  static slug = "wppb_userlisting";

  _shouldReload(oldProps, newProps) {
    return (
      oldProps.userlisting_name !== newProps.userlisting_name ||
      oldProps.toggle_single !== newProps.toggle_single ||
      oldProps.single_id !== newProps.single_id ||
      oldProps.field_name !== newProps.field_name ||
      oldProps.meta_value !== newProps.meta_value ||
      oldProps.include_id !== newProps.include_id ||
      oldProps.exclude_id !== newProps.exclude_id
    );
  }

  _reloadFormData(props) {
    var formData = new FormData();

    formData.append("action", "wppb_divi_extension_ajax");
    formData.append("form_type", "ul");
    formData.append("userlisting_name", props.userlisting_name);
    formData.append("single", props.toggle_single);
    formData.append("id", props.single_id);
    formData.append("field_name", props.field_name);
    formData.append("meta_value", props.meta_value);
    formData.append("include", props.include_id);
    formData.append("exclude", props.exclude_id);

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

export default UserListing;
