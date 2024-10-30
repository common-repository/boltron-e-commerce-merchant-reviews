jQuery(document).ready( function( $ ) {

    const { __, _x, _n, _nx } = window.wp.i18n;

    $( '.options_group ._sku_field label' ).html( `<abbr title="${__( 'Stock Keeping Unit / Manufacturer Part Number', 'boltron' )}">SKU / MPN</abbr>` );

    $( '.boltron_inventory' ).insertAfter( '.options_group ._sku_field' );

    let wrapper = $( '.term-boltron-cat-ghsop-cat-wrap, ._product_cat_field' );

    if ( wrapper.length > 0 ) {

        $( wrapper.find( 'select' ) ).change( e => {
            e.preventDefault();

            let elm = $( e.target ),
                elms = wrapper.find( 'select' ),
                next_all = elm.nextAll( 'select' ),
                val = elm.val().trim();

            if ( val == '' || next_all.length <= 0 ) return false;

            // console.log( val );
            
            var seen = false,
                current = false,
                obj = Boltron_Product_Cats;

            elms.each( ( k, v ) => {

                var t_val = $( v ).val().trim();

                // if ( typeof obj[ t_val ] == 'undefined' ) return false;

                if ( t_val == -1 ) $( v ).nextAll( 'select' ).html( '<option value="-1">== Select a Category ==</option>' ).hide();

                if ( seen ) {

                    var cats    = Object.keys( obj ),
                        options = '<option value="-1">== Select a Category ==</option>';

                    // Populate the dropdown
                    for ( var i = 0; i < cats.length; i++ ) {
                        var name = cats[i],
                            selected = $( v ).is( elm ) && name == val ? ' selected' : '';

                        options += `<option value="${name}"${selected}>${name}</option>`;
                    }

                    $( v ).html( options ).show().focus();
                }

                obj = obj[ t_val ];

                if ( $( v ).is( elm ) ) {
                    current = true;
                    seen = true;
                }

                if ( obj == null || obj == '' ) {
                    $( v ).nextAll( 'select' ).html( '<option value="-1">== Select a Category ==</option>' ).hide();

                    return false;
                }

                // console.log( obj );
            } );

            return false;
        } );

        // $( wrapper.find( 'select' ) ).change();
    }
});
