jQuery(document).ready(function($) {

    tinymce.create(
        'tinymce.plugins.transifex_stats_plugin',

        {
            init : function(ed, url)
            {

                // Register command for when button is clicked
                ed.addCommand('transifex_stats_insert_shortcode', function() {
                    content =  '[transifex_stats project="" resource=""]';

                    tinymce.execCommand('mceInsertContent', false, content);
                });

                // Register buttons - trigger above command when clicked
                ed.addButton('transifex_stats', {
                    title : 'Insert shortcode',
                    cmd : 'transifex_stats_insert_shortcode'
                });
            }
        }
    );

    // Register our TinyMCE plugin
    // first parameter is the button ID1
    // second parameter must match the first parameter of the tinymce.create() function above
    tinymce.PluginManager.add( 'transifex_stats', tinymce.plugins.transifex_stats_plugin );
});
