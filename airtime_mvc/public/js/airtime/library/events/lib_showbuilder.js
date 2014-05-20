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
    
    mod.createDraggable = function($table) {
    	var $sortable = $("#show_builder_table"),
			$table = mod.getActiveTable();
		
		//don't create draggable if there's nothing to drag to on the page.
		if ($sortable.length === 0) {
			return;
		}
		
		$table.find("tbody tr").draggable({
			appendTo: $table.parents(".wrapper"),
            cancel: "td.dataTables_empty, input",
            handle: "td",
            helper: function() {
                var $el = $(this),
                	selected = mod.getVisibleChosen().length,
	                container,
	                thead = $("#show_builder_table thead"),
	                colspan = thead.find("th").length,
	                width = thead.find("tr:first").width(),
	                message;

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

                container = $('<div/>')
	                .attr('id', 'draggingContainer')
	                .append('<tr/>')
	                .find("tr")
		                .append('<td/>')
		                .find("td")
			                .attr("colspan", colspan)
			                .width(width)
			                .addClass("ui-state-highlight")
			                .append(message)
			                .end()
	                .end();

                return container;
            },
            cursor: 'pointer',
            cursorAt: {
                top: 30,
                left: 100
            },
            connectToSortable : '#show_builder_table'
		});
    };
    
    mod.setupToolbar = function(tabId) {
        var $toolbar = $("#"+tabId+" .fg-toolbar:first"),
        	$menu = mod.createToolbarButtons();

        $toolbar.append($menu);
        
    };
    
    mod.checkAddButton = function($pane) {
    	var $selected = $pane.find("."+mod.LIB_SELECTED_CLASS),
			$button = $pane.find("." + mod.LIB_ADD_CLASS);
		
		if ($selected.length > 0) {
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
    
    function getScheduleCursors() {
    	var aSchedIds = [];
	
    	// process selected schedule rows to add media after.
	    $("#show_builder_table tr.cursor-selected-row").each(function(i, el) {
	    	var data = $(el).data("aData");
	    	
	    	aSchedIds.push( {
	            "id" : data.id,
	            "instance" : data.instance,
	            "timestamp" : data.timestamp
	        });
	    });

	    return aSchedIds;
    }
    
    function scheduleMedia(aMediaIds) {
    	var cursorInfo = getScheduleCursors();
    	
    	if (cursorInfo.length == 0) {
            alert($.i18n._("Please select a cursor position on timeline."));
            return false;
        }
        
        AIRTIME.showbuilder.fnAdd(aMediaIds, cursorInfo);
    }
    
    //data is the aData of the tr element.
    mod.dblClickAdd = function(data) {
    	scheduleMedia([data.Id]);
    };
    
    mod.addButtonClick = function() {
    	scheduleMedia(mod.getVisibleChosen());
    };
    
    mod.initCustomEvents = function() {

    };
    
    return AIRTIME;

}(AIRTIME || {}));