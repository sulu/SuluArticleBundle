/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        suluarticle: '../../suluarticle/js',
        suluarticlecss: '../../suluarticle/css'
    }
});

define(function() {

    'use strict';

    return {

        name: 'Sulu Article Bundle',

        initialize: function(app) {
            app.components.addSource('suluarticle', '/bundles/suluarticle/dist/components')
        }
    }
});
