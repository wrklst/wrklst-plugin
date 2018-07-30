/*
var Library = wp.media.controller.Library;
var oldMediaFrame = wp.media.view.MediaFrame.Select;

//wp.media.view.MediaFrame.Select

// Extending the current media library frame to add a new tab
wp.media.view.MediaFrame.Select = oldMediaFrame.extend({

    initialize: function() {
        // Calling the initalize method from the current frame before adding new functionality
        oldMediaFrame.prototype.initialize.apply( this, arguments );
        var options = this.options;
        // Adding new tab
        this.states.add([
            new Library({
                id:         'inserts',
                title:      'WrkLst Artwork Media',
                priority:   20,
                toolbar:    'main-insert',
                filterable: 'all',
                library:    wp.media.query( options.library ),
                multiple:   false,
                editable:   false,
                library:  wp.media.query( _.defaults({
                    // Adding a new query parameter
                    wrklst_only: 1,

                }, options.library ) ),

                // Show the attachment display settings.
                displaySettings: true,
                // Update user settings when users adjust the
                // attachment display settings.
                displayUserSettings: true
            }),
        ]);
    },

});
*/
