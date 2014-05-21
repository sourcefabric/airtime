var AIRTIME = (function(AIRTIME) {
    var mod;

    if (AIRTIME.library === undefined) {
        AIRTIME.library = {};
    }

    mod = AIRTIME.library;
    
    var datatablesSettings = {
		"lib_audio": {
			draggable: true
		},
		"lib_webstream": {
			draggable: true
		},
		"lib_playlist": {
			draggable: true
		}
	};
    
    mod.datatablesEventSettings = function() {
    	return datatablesSettings;
    };
    
    mod.createDraggable = function() {
    	var $sortable = $("#spl_sortable"),
    		$table = mod.getActiveTable();
    	
    	//don't create draggable if there's nothing to drag to on the page.
    	if ($sortable.length === 0) {
    		return;
    	}
    	
    	$table.find("tbody tr").draggable({
            helper : function(event, element) {
                var $el = $(this),
                	selected = mod.getVisibleChosen().length,
                	container,
                	message,
                	li = $("#side_playlist ul[id='spl_sortable'] li:first"),
                    width = li.width(), height = 55;
                
                if (width > 798) width = 798;

                // dragging an element that has an unselected
                // checkbox.
                if (mod.isChosenItem($el) === false) {
                    selected++;
                }

                if (selected === 1) {
                    message = $.i18n._("Adding 1 Item");
                }
                else {
                    message = sprintf($.i18n._("Adding %s Items"), selected);
                }

                container = $('<div class="helper"/>')
                	.append("<li/>")
                	.find("li")
                		.addClass("ui-state-default playlist-temp-holder")
                		.append("<div/>")
                        .find("div")
                        	.addClass("list-item-container")
                        	.append(message)
                        	.end()
                        .width(width)
                        .height(height)
                        .end();

                return container;
            },
            appendTo: $("body"),
            cancel: "td.dataTables_empty, input",
            handle: "td",
            cursor: 'pointer',
            cursorAt: {
                top: 30,
                left: 100
            },
            connectToSortable : '#spl_sortable'
        });
    };
    
    mod.setupToolbar = function(tabId) {
        var $toolbar = $("#"+tabId+" .fg-toolbar:first"),
        	$menu = mod.createToolbarButtons();

        $toolbar.append($menu);  
    };
    
    mod.checkAddButton = function($pane) {
    	var $selected = $pane.find("."+mod.LIB_SELECTED_CLASS),
    		$button = $pane.find("." + mod.LIB_ADD_CLASS),
    		$playlistContentEl = $("#spl_sortable");
    	
    	if ($selected.length > 0 && $playlistContentEl.length > 0) {
    		AIRTIME.button.enableButton($button);
    	}
    	else {
    		AIRTIME.button.disableButton($button);
    	}
    };
    
    mod.checkToolBarIcons = function() {
    	var tabId = mod.getActiveTabId();
    		$pane = $("#"+tabId);
    	
    	mod.checkAddButton($pane);
        mod.checkDeleteButton($pane);
    };
    
    //takes an array of media ids
    function addToPlaylist(aIds) {
    	var $insertAfter = $("#spl_sortable li:last").not(".spl_empty");
    	var insertAfterId = null;
    	
    	if ($insertAfter.length > 0) {
    		insertAfterId = parseInt($insertAfter.attr("id").split("_").pop(), 10);
    	}
    		
    	AIRTIME.playlist.addItems(aIds, insertAfterId);
    };
    
    //data is the aData of the tr element.
    mod.dblClickAdd = function(data) {
    	var $playlistContentEl = $("#spl_sortable");
    	
    	if ($playlistContentEl.length > 0) {
    		addToPlaylist([data.Id]);
    	}
    };
    
    mod.addButtonClick = function() {
    	addToPlaylist(mod.getVisibleChosen());
    };
    
    mod.openPlaylist = function(data) {
    	var mediaId = data.id;
    	
    	AIRTIME.playlist.edit(mediaId);
    };
    
    function updatePlaylistTable(event, type) {
		
		//set up library draggables if they don't already exist.
		var tab = mod.getActiveTabId();
		var table = mod.getActiveDatatable();
		
		if (tab = "lib_playlist") {
			table.fnDraw();
		}
		else if (type === "static") {
			var $draggables = mod.findLibraryDraggables();
    		
    		if ($draggables.length === 0) {
    			mod.createDraggable();
    		}
		}
	}
     
    mod.initCustomEvents = function() {
    	var $playlist = $("#side_playlist");

    	$playlist.on("playlistnew", updatePlaylistTable);
    	
    	$playlist.on("playlistupdate", updatePlaylistTable);
    	
    	$playlist.on("playlistdelete", function() {
    		mod.destroyLibraryDraggables();
    	});
    	
    	$playlist.on("playlistclose", function() {
    		mod.destroyLibraryDraggables();
    	});
    };
    
    return AIRTIME;

}(AIRTIME || {}));