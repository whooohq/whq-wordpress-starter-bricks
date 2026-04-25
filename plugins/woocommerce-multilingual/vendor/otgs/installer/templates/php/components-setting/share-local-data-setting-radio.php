<div style="width: 660px;" class="otgs-installer-component-setting" data-has-setting="<?php echo esc_attr( $model->has_setting ); ?>">
	<?php if ( $model->hasValue( 'custom_description_text' ) ) { ?>
        <p>
			<?php echo wp_kses_post( $model->custom_description_text ); ?>
        </p>
	<?php } ?>
	<span class="spinner otgs-components-report-setting-spinner"></span>
	<ul>
		<li>
			<label for="<?php echo esc_attr( $model->nonce->action . $model->nonce->value ); ?>-yes">
				<input
						type="radio"
						<?php if ( $model->has_setting && $model->is_repo_allowed ) { ?>
							checked="checked"
						<?php } ?>
						id="<?php echo esc_attr( $model->nonce->action . $model->nonce->value ); ?>-yes"
						class="js-otgs-components-report-user-choice"
						value="1"
						name="otgs-components-report-user-choice"
						data-nonce-action="<?php echo esc_attr( $model->nonce->action ); ?>"
						data-nonce-value="<?php echo esc_attr( $model->nonce->value ); ?>"
						data-repo="<?php echo esc_attr( $model->repo ); ?>"
				/>

				<?php
				if ( $model->hasValue( 'custom_radio_label_yes' ) ) {
					echo  wp_kses_post( $model->custom_radio_label_yes );
				} else {
					echo  wp_kses_post( $model->strings->radio_report_yes );
				}
				?>
			</label>
		</li>
		<li>
			<label for="<?php echo esc_attr( $model->nonce->action . $model->nonce->value ); ?>-no">
				<input
						type="radio"
						<?php if ( $model->has_setting && ! $model->is_repo_allowed ) { ?>
							checked="checked"
						<?php } ?>
						id="<?php echo esc_attr( $model->nonce->action . $model->nonce->value ); ?>-no"
						class="js-otgs-components-report-user-choice"
						value="0"
						name="otgs-components-report-user-choice"
						data-nonce-action="<?php echo esc_attr( $model->nonce->action ); ?>"
						data-nonce-value="<?php echo esc_attr( $model->nonce->value ); ?>"
						data-repo="<?php echo esc_attr( $model->repo ); ?>"
				/>
				<?php
				if ( $model->hasValue( 'custom_radio_label_no' ) ) {
					echo  wp_kses_post( $model->custom_radio_label_no );
				} else {
					echo  wp_kses_post( $model->strings->radio_report_no );
				}
				?>
			</label>
		</li>
	</ul>

    <p class="otgs-installer-component-privacy-policy">
        <a
                href="<?php echo esc_url( $model->privacy_policy_url ); ?>"
                target="_blank"
                rel="noopener"
                class="otgs-external-link"
        >
			<?php
			if ( $model->hasValue( 'custom_privacy_policy_text' ) ) {
				echo wp_kses_post( $model->custom_privacy_policy_text );
			} else {
				echo wp_kses_post( $model->privacy_policy_text );
			}
			?>
        </a>
    </p>

	<?php if ( $model->hasValue( 'sharing_data_details_text' ) && $model->hasValue( 'sharing_data_details_url' ) ) { ?>
        <p class="otgs-installer-component-privacy-policy">
            <a
                    href="<?php echo esc_url( $model->sharing_data_details_url ); ?>"
                    target="_blank"
                    rel="noopener"
                    class="otgs-external-link"
            >
				<?php
				echo wp_kses_post( $model->sharing_data_details_text )
				?>
            </a>
        </p>
	<?php } ?>
</div>
