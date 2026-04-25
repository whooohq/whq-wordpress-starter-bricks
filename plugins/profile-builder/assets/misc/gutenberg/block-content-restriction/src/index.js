import { assign, has } from "lodash";

import { addFilter } from "@wordpress/hooks";
import { createHigherOrderComponent } from "@wordpress/compose";
import { __ } from "@wordpress/i18n";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody } from "@wordpress/components";

import WPPBBlockContentRestrictionControlsCommon from "./controls.js";

/**
 * Add the content restriction inspector controls in the editor
 */
function WPPBBlockContentRestrictionControls(props) {
    const { name, attributes, setAttributes } = props;
    const { wppbContentRestriction } = attributes;

    const contentRestrictionActivated = JSON.parse(
        wppbBlockEditorData.content_restriction_activated,
    );

    // Abort if content restriction is not enabled, if the block type does not have the
    // wppbContentRestriction attribute registered or if the block is one of the Content Restriction blocks
    if (
        !contentRestrictionActivated ||
        !has(attributes, "wppbContentRestriction") ||
        [
            "wppb/content-restriction-start",
            "wppb/content-restriction-end",
            "wppb/content-restriction-start",
            "wppb/content-restriction-start",
        ].includes(name)
    ) {
        return null;
    }

    return (
        <InspectorControls>
            <PanelBody
                title={__(
                    "Profile Builder Content Restriction",
                    "profile-builder",
                )}
                className="profile-builder-content-restriction-settings"
                initialOpen={wppbContentRestriction.panel_open}
                onToggle={(value) =>
                    setAttributes({
                        wppbContentRestriction: assign(
                            { ...wppbContentRestriction },
                            { panel_open: !wppbContentRestriction.panel_open },
                        ),
                    })
                }
            >
                <WPPBBlockContentRestrictionControlsCommon {...props} />
            </PanelBody>
        </InspectorControls>
    );
}

/**
 * Add the content restriction settings attribute
 */
function WPPBContentRestrictionAttributes(settings) {
    let contentRestrictionAttributes = {
        wppbContentRestriction: {
            type: "object",
            properties: {
                user_roles: {
                    type: "array",
                },
                users_ids: {
                    type: "string",
                },
                display_to: {
                    type: "string",
                },
                enable_message_logged_in: {
                    type: "bool",
                },
                enable_message_logged_out: {
                    type: "bool",
                },
                message_logged_in: {
                    type: "string",
                },
                message_logged_out: {
                    type: "string",
                },
                panel_open: {
                    type: "bool",
                },
            },
            default: {
                user_roles: [],
                users_ids: "",
                display_to: "all",
                enable_message_logged_in: false,
                enable_message_logged_out: false,
                message_logged_in: "",
                message_logged_out: "",
                panel_open: false,
            },
        },
    };

    // The Content Restriction Start block should not have an 'All Users' option
    if (settings.attributes.wppb_content_restriction_block_start) {
        contentRestrictionAttributes.wppbContentRestriction.default.display_to =
            "";
    }

    // Do not add the content restriction settings attribute for these blocks
    if (
        settings.attributes.wppb_content_restriction_block_end ||
        settings.attributes.pms_content_restriction_block_start ||
        settings.attributes.pms_content_restriction_block_end
    ) {
        return settings;
    }

    settings.attributes = assign(
        settings.attributes,
        contentRestrictionAttributes,
    );
    return settings;
}
addFilter(
    "blocks.registerBlockType",
    "profile-builder/attributes",
    WPPBContentRestrictionAttributes,
);

/**
 * Filter the block edit object and add content restriction controls
 */
const blockWPPBContentRestrictionControls = createHigherOrderComponent(
    (BlockEdit) => {
        return (props) => {
            return (
                <>
                    <BlockEdit {...props} />
                    <WPPBBlockContentRestrictionControls {...props} />
                </>
            );
        };
    },
    "blockWPPBContentRestrictionControls",
);
addFilter(
    "editor.BlockEdit",
    "profile-builder/inspector-controls",
    blockWPPBContentRestrictionControls,
    100, // above Advanced controls
);
