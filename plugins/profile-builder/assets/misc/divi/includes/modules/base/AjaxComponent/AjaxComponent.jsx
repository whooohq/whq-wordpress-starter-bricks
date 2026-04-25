import React, { Component } from "react";
import $ from "jquery";

class AjaxComponent extends Component {
  static slug = "wppb_ajax_component";

  constructor(props) {
    super(props);
    this.state = {
      isLoaded: false,
      result: null,
      error: null,
    };
  }

  componentDidMount() {
    this._reload(this.props);
  }

  componentDidUpdate(prevProps) {
    if (this._shouldReload(prevProps, this.props)) {
      this.setState({
        isLoaded: false,
      });
      this._reload(this.props);
    }
  }

  _shouldReload(oldProps, newProps) {
    throw new Error(
      "You must implement the method _shouldReload(oldProps, newProps)",
    );
    //Example
    //return oldProps.value_which_needs_to_be_changed_to_cause_reload != newProps.value_which_needs_to_be_changed_to_cause_reload;
  }

  _reloadFormData(props) {
    throw new Error("You must implement the method _reloadFormData(props)");
    //Example Form Data:
    // var body = new FormData();
    // body.append('action', 'my_ajax_call');
    // body.append('post_id', window.ETBuilderBackend.postId);
    // body.append('some_field', props.some_field);
    // body.append('nonce', window.BuilderData.nonces.textfield);
    // return body;
  }

  _reload(props) {
    var formData = this._reloadFormData(props);

    formData.append( "pb_nonce" , window.ProfileBuilderDiviExtensionBuilderData.nonces.pb_divi_render_form_nonce );

    $.ajax({
      url: window.et_fb_options.ajaxurl,
      type: "POST",
      contentType: false,
      processData: false,
      data: formData,
      success: (response) => {
        response = JSON.parse(response);

        if (response.errors) {
          this.setState({
            isLoaded: true,
            error: response.errors,
          });
        } else {
          this.setState({
            isLoaded: true,
            result: response,
          });
        }
      },
    });
  }

  _render() {
    throw new Error("You must implement the method _render()");
  }

  render() {
    if (this.state.error) {
      return <div>{this.state.error.message}</div>;
    } else if (!this.state.isLoaded) {
      return (
        <div
          className="wppb_loading_indicator"
          style={{
            height: 100 + "px",
            minWidth: 100 + "px",
          }}
        >
          <div className="et-fb-loader-wrapper">
            <div className="et-fb-loader"></div>
          </div>
        </div>
      );
    } else {
      return this._render();
    }
  }
}

export default AjaxComponent;
