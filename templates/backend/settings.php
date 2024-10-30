<div class="wrap boltron-wrapper">

    <h1><?php printf( __( ' %s Settings.', 'boltron' ), BTRN()->name); ?></h1>

    <form id="boltron_settings_form" action="" method="POST">

        <?php wp_nonce_field( 'boltron_nonce', 'boltron_settings' ); ?>

        <table class="form-table">
            <tbody>
                <?php if ( ! empty( BTRN()->get_option( 'merchant_id' ) ) ) : ?>

                <tr valign="top">
                    <th scope="row">
                        <label for="boltron_enable_cs"><?php esc_html_e( 'Customer Support Widget', 'boltron' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="boltron_enable_cs" name="boltron_enable_cs" <?php checked( BTRN()->get_option( 'enable_cs' ), true ); ?>>
                            <span><?php esc_html_e( 'Enable/Disable' ) ?></span>
                        </label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="boltron_floating_widget"><?php esc_html_e( 'Floating Widget', 'boltron' ); ?></label>
                    </th>
                    <td>
                        <select id="boltron_floating_widget" name="boltron_floating_widget">
                            <?php
                            $floating_locations = [
                                'left'      => __( 'Left', 'boltron' ),
                                'right'     => __( 'Right', 'boltron' ),
                                'disabled'  => __( 'Disabled', 'boltron' ),
                            ];

                            foreach( $floating_locations as $key => $label ) :
                            ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, BTRN()->get_option( 'floating_widget' ) ) ?>><?php echo esc_html( $label ) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="boltron_shortcode"><?php esc_html_e( 'Widget Shortcode', 'boltron' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="boltron_shortcode" class="regular-text" value="[boltron]" readonly>
                        <button type="button" onclick="copyShortcode(event);" class="button button-secondary"><?php esc_html_e( 'Copy shortcode', 'boltron' ); ?></button>
                        <p><?php printf( __( 'You can place this shortcode anywhere on your website to display the grading system.', 'boltron' ) ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="boltron_merchant_id"><?php esc_html_e( 'Merchant ID', 'boltron' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="boltron_merchant_id" class="regular-text" value="<?php echo esc_attr( BTRN()->get_option( 'merchant_id' ) ); ?>" readonly disabled>
                    </td>
                </tr>
                <?php endif; ?>

                <tr valign="top">
                    <th scope="row">
                        <label for="boltron_api_key"><?php esc_html_e( 'Merchant API Key', 'boltron' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="boltron_api_key" name="boltron_api_key" class="regular-text" value="<?php echo esc_attr( BTRN()->get_option('api_key') ); ?>" required>

                        <?php if ( ! empty( BTRN()->get_option('api_key') ) ) : ?>
                        <input type="hidden" name="key_check">
                        <button type="button" onclick="checkApiKey();" class="button button-secondary"><?php esc_html_e( 'Check API key', 'boltron' ); ?></button>
                        <?php endif; ?>

                        <p><?php printf( __( 'Get your merchant API key from Boltron\'s <a href="%s">dashboard</a>', 'boltron' ), BTRN()->host . '/dashboard' ); ?></p>
                    </td>
                </tr>

            </tbody>
        </table>

        <p class="submit">
            <input type="submit" value="<?php esc_attr_e( 'Save Changes', 'boltron' ); ?>" class="button button-primary" name="boltron_submit">
        </p>

    </form>

</div>

<script>
    let checkApiKey = () => {
        let form = document.getElementById( 'boltron_settings_form' ),
            input = form.querySelector( '[name=key_check]' );

        input.value = 'yes';

        form.submit();
    }

    let copyShortcode = (e) => {
        let form = document.getElementById( 'boltron_settings_form' ),
            input = form.querySelector( '#boltron_shortcode' ),
            elm = e.target,
            content = elm.innerHTML;

        input.select();
        input.setSelectionRange(0, 99999); /*For mobile devices*/
        document.execCommand( 'copy' );

        elm.innerHTML = '<?php esc_html_e( 'Copied!', 'boltron' ); ?>';

        setTimeout(() => elm.innerHTML = content, 1000);
    }
</script>