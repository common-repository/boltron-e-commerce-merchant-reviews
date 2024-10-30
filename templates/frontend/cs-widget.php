<?php
$host               = BTRN()->host;
$wrapper            = 'BoltronWidgetWrapper';
$enable_cs          = boolval( BTRN()->get_option( 'enable_cs' ) );
$merchant_id        = BTRN()->get_option( 'merchant_id' );
$floating_widget    = trim( BTRN()->get_option( 'floating_widget' ) );

if ( ! $enable_cs ) return;

$style = ! in_array( $floating_widget, [ 'left', 'disabled' ] ) ? '' : 'bottom: 25px;right: 25px;';
?>

<div class="boltron-cs-widget" style="<?php echo $style; ?>">
    <div class="icon-wrapper">
        <img src="<?php echo BTRN()->uri . 'assets/images/support-light.svg' ?>">
    </div>

    <div class="iframe-wrapper">
        <div class="iframe-header">
            <span><?php esc_html_e( 'Customer support', 'boltron' ); ?></span>
            <span class="close-icon">&times;</span>
        </div>
        <iframe id="chat-frame" class="iframeclass" scrolling="no" allow="autoplay" src="<?php echo $host . "/chat/public/$merchant_id" ?>"></iframe>
    </div>
</div>

<script>
    let widget          = document.querySelector( '.boltron-cs-widget' );
    let icon_wrapper    = widget.querySelector( '.icon-wrapper' );
    let iframe_wrapper  = widget.querySelector( '.iframe-wrapper' );
    let close_icon      = iframe_wrapper.querySelector( '.close-icon' );

    icon_wrapper.addEventListener( 'click', e => widget.classList.add( 'iframe' ) );
    close_icon.addEventListener( 'click', e => widget.classList.remove( 'iframe' ) );

    let checkIframeLoaded = () => {
        let iframe = document.getElementById('chat-frame');
        let iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

        if (  iframeDoc.readyState  == 'complete' ) {

            let chat_list = iframeDoc.querySelector( '.chat-message-list' );

            // if ( chat_list.length > 0 ) widget.classList.add( 'iframe' );

            return;
        } 

        window.setTimeout( checkIframeLoaded, 100 );
    }

    checkIframeLoaded();
</script>