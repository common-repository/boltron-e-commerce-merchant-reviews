jQuery(document).ready( function( $ ) {

    if ( typeof Boltron_Backend !== 'undefined' ) {

        if ( Boltron_Backend.do_url_mod == true ) {

            var url = new URI(),
                obj = Boltron_Backend.url_params;

                /**
                * Change window url
                * 
                * @param title
                * @param url
                */
                change_url = function( title, url ) {

                    var obj = { Title: title, Url: url };

                    history.pushState( obj, obj.Title, obj.Url );

                }

            url.removeQuery( obj );

            change_url( '', url );

        }
    }

});