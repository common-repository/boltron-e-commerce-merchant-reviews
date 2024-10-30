/**
 * For Guternberg Editor
 */

'use strict';

(function (element, blocks, i18n) {

    let el = element.createElement;

    blocks.registerBlockType('boltron/block', {
        title: 'Boltron',
        description: i18n.__('Boltron Merchants', 'boltron'),
        icon: 'editor-code',
        category: 'embed',

        save() {
            return el( 'div', null, '[boltron]');
        }
    });

})(
    window.wp.element,
    window.wp.blocks,
    window.wp.i18n
);