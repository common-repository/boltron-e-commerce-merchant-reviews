/**
 * For Classic Editor
 */

'use strict';

(function (i18n) {

    tinymce.PluginManager.add( 'boltron', function( editor ) {

    	// Add a button that will insert the shortcode
    	editor.addButton( 'boltron', {
    		icon: 'icon dashicons-editor-code',
    		text: 'Boltron',
			tooltip: i18n.__('Boltron Merchants', 'boltron'),
    		onclick: function () {
    			var sc = wp.shortcode.string({
    				tag: 'boltron',
    				attrs: {},
    				type: 'single'
    			});

    			editor.insertContent(sc);
    		}
		});

    });
    
}) ( window.wp.i18n );