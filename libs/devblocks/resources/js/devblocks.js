function DevblocksClass() {
	/* Source: http://bytes.com/forum/thread90068.html */
	// [TODO] Does this matter with caret.js anymore?
	this.getSelectedText = function() {
		if (window.getSelection) { // recent Mozilla
			var selectedString = window.getSelection();
		} else if (document.all) { // MSIE 4+
			var selectedString = document.selection.createRange().text;
		} else if (document.getSelection) { //older Mozilla
			var selectedString = document.getSelection();
		};
		
		return selectedString;
	}
	
	this.getFormEnabledCheckboxValues = function(form_id,element_name) {
		return $("#" + form_id + " INPUT[name='" + element_name + "']:checked")
		.map(function() {
			return $(this).val();
		})
		.get()
		.join(',')
		;
	}

	this.resetSelectElements = function(form_id,element_name) {
		// Make sure the view form exists
		var viewForm = document.getElementById(form_id);
		if(null == viewForm) return;

		// Make sure the element is present in the form

		var elements = viewForm.elements[element_name];
		if(null == elements) return;

		var len = elements.length;
		var ids = new Array();
		
		if(null == len && null != elements.selectedIndex) {
			elements.selectedIndex = 0;

		} else {
			for(var x=len-1;x>=0;x--) {
				elements[x].selectedIndex = 0;
			}
		}
	}
	
	this.showError = function(target, message, animate) {
		$html = $('<div class="ui-widget"><div class="ui-state-error ui-corner-all" style="padding:0 0.5em;margin:0.5em;"><p><span class="ui-icon ui-icon-alert" style="margin-right:.3em;float:left;"></span>'+message+'</p></div></div>');
		$status = $(target).html($html).show();
		
		animate = (null == animate || false != animate) ? true: false;
		if(animate)
			$status.effect('slide',{ direction:'up', mode:'show' },250);
		
		return $status;
	}
	
	this.showSuccess = function(target, message, autohide, animate) {
		$html = $('<div class="ui-widget"><div class="ui-state-highlight ui-corner-all" style="padding:0 0.5em;margin:0.5em;"><p><span class="ui-icon ui-icon-info" style="margin-right:.3em;float:left;"></span>'+message+'</p></div></div>');
		$status = $(target).html($html).show();
		
		animate = (null == animate || false != animate) ? true: false; 
		if(animate)
			$status.effect('slide',{ direction:'up', mode:'show' },250);
		if(animate && (autohide || null == autohide))
			$status.delay(5000).effect('slide',{ direction:'up', mode:'hide' }, 250);
			
		return $status;
	}
	
	this.getDefaultjQueryUiTabOptions = function() {
		return {
			activate: function(event, ui) {
				var tabsId = ui.newPanel.closest('.ui-tabs').attr('id');
				var index = ui.newTab.index();
				
				var selectedTabs = {};
				
				if(undefined != localStorage.selectedTabs)
					selectedTabs = JSON.parse(localStorage.selectedTabs);
				
				selectedTabs[tabsId] = index;
				localStorage.selectedTabs = JSON.stringify(selectedTabs);
			},
			beforeLoad: function(event, ui) {
				var tab_title = ui.tab.find('> a').first().clone();
				tab_title.find('div.tab-badge').remove();
				ui.panel.html('<div style="font-size:18px;font-weight:bold;text-align:center;padding:10px;margin:10px;">Loading: ' + $.trim(tab_title.text()) + '<br><span class="cerb-ajax-spinner"></span></div>');
			},
		};
	}
	
	this.getjQueryUiTabSelected = function(tabsId, activeTab) {
		if(undefined != activeTab) {
			var $tabs = $('#' + tabsId);
			var $activeTab = $tabs.find('li[data-alias="' + activeTab + '"]');
			
			if($activeTab.length > 0) {
				return $activeTab.index();
			}
		}
		
		if(undefined == localStorage.selectedTabs)
			return 0;
		
		var selectedTabs = JSON.parse(localStorage.selectedTabs);
		
		if(typeof selectedTabs != "object" || undefined == selectedTabs[tabsId])
			return 0;
		
		return selectedTabs[tabsId];
	}
};
var Devblocks = new DevblocksClass();

function selectValue(e) {
	return e.options[e.selectedIndex].value;
}

function interceptInputCRLF(e,cb) {
	var code = (window.Event) ? e.which : event.keyCode;
	
	if(null != cb && code == 13) {
		try { cb(); } catch(e) { }
	}
	
	return code != 13;
}

/* From:
 * http://www.webmasterworld.com/forum91/4527.htm
 */
function setElementSelRange(e, selStart, selEnd) { 
	if (e.setSelectionRange) { 
		e.focus(); 
		e.setSelectionRange(selStart, selEnd); 
	} else if (e.createTextRange) { 
		var range = e.createTextRange(); 
		range.collapse(true); 
		range.moveEnd('character', selEnd); 
		range.moveStart('character', selStart); 
		range.select(); 
	} 
}

function scrollElementToBottom(e) {
	if(null == e) return;
	e.scrollTop = e.scrollHeight - e.clientHeight;
}

function toggleDiv(divName,state) {
	var div = document.getElementById(divName);
	if(null == div) return;
	var currentState = div.style.display;
	
	if(null == state) {
		if(currentState == "block") {
			div.style.display = 'none';
		} else {
			div.style.display = 'block';
		}
	} else if (null != state && (state == '' || state == 'block' || state == 'inline' || state == 'none')) {
		div.style.display = state;
	}
}

function checkAll(divName, state) {
	var div = document.getElementById(divName);
	if(null == div) return;
	
	var boxes = div.getElementsByTagName('input');
	var numBoxes = boxes.length;
	
	for(x=0;x<numBoxes;x++) {
		if(null != boxes[x].name) {
			if(state == null) state = !boxes[x].checked;
			boxes[x].checked = (state) ? true : false;
			// This may not be needed when we convert to jQuery
			$(boxes[x]).trigger('change');
			$(boxes[x]).trigger('check');
		}
	}
}

// [JAS]: [TODO] Make this a little more generic?
function appendTextboxAsCsv(formName, field, oLink) {
	var frm = document.getElementById(formName);
	if(null == frm) return;
	
	var txt = frm.elements[field];
	var sAppend = '';
	
	// [TODO]: check that the last character(s) aren't comma or comma space
	if(0 != txt.value.length && txt.value.substr(-1,1) != ',' && txt.value.substr(-2,2) != ', ')
		sAppend += ', ';
		
	sAppend += oLink.innerHTML;
	
	txt.value = txt.value + sAppend;
}

var loadingPanel;
function showLoadingPanel() {
	if(null != loadingPanel) {
		hideLoadingPanel();
	}
	
	var options = {
		bgiframe : true,
		autoOpen : false,
		closeOnEscape : false,
		draggable : false,
		resizable : false,
		modal : true,
		width : '300px',
		title : 'Please wait...'
	};

	if(0 == $("#loadingPanel").length) {
		$("body").append("<div id='loadingPanel' style='display:none;text-align:center;padding-top:20px;'></div>");
	}

	// Set the content
	$("#loadingPanel").html('<span class="cerb-ajax-spinner"></span><h3>Loading, please wait...</h3>');
	
	// Render
	loadingPanel = $("#loadingPanel").dialog(options);
	
	loadingPanel.siblings('.ui-dialog-titlebar').hide();
	
	loadingPanel.dialog('open');
}

function hideLoadingPanel() {
	loadingPanel.unbind();
	loadingPanel.dialog('destroy');
	loadingPanel = null;
}

function genericAjaxPopupFind($sel) {
	$devblocksPopups = $('#devblocksPopups');
	$data = $devblocksPopups.data();
	$element = $($sel).closest('DIV.devblocks-popup');
	for($key in $data) {
		if($element.attr('id') == $data[$key].attr('id'))
			return $data[$key];
	}
	
	return null;
}

function genericAjaxPopupFetch($layer) {
	return $('#devblocksPopups').data($layer);
}

function genericAjaxPopupClose($layer, $event) {
	$popup = genericAjaxPopupFetch($layer);
	if(null != $popup) {
		try {
			if(null != $event)
				$popup.trigger($event);
		} catch(e) { if(window.console) console.log(e);  }
		
		try {
			$popup.dialog('close');
		} catch(e) { if(window.console) console.log(e); }
		
		return true;
	}
	return false;
}

function genericAjaxPopupDestroy($layer) {
	$popup = genericAjaxPopupFetch($layer);
	if(null != $popup) {
		genericAjaxPopupClose($layer);
		try {
			$popup.dialog('destroy');
			$popup.unbind();
		} catch(e) { }
		$($('#devblocksPopups').data($layer)).remove(); // remove DOM
		$('#devblocksPopups').removeData($layer); // remove from registry
		return true;
	}
	return false;
}

function genericAjaxPopupRegister($layer, $popup) {
	$devblocksPopups = $('#devblocksPopups');
	
	if(0 == $devblocksPopups.length) {
		$('body').append("<div id='devblocksPopups' style='display:none;'></div>");
		$devblocksPopups = $('#devblocksPopups');
	}
	
	$('#devblocksPopups').data($layer, $popup);
}

function genericAjaxPopup($layer,request,target,modal,width,cb) {
	// Default options
	var options = {
		bgiframe : true,
		autoOpen : false,
		closeOnEscape : true,
		draggable : true,
		modal : false,
		resizable : true,
		width : '600px',
		close: function(event, ui) {
			$(this).unbind().find(':focus').blur();
		}
	};
	
	var $popup = null;
	
	// Restore position from previous dialog?
	if(target == 'reuse') {
		$popup = genericAjaxPopupFetch($layer);
		if(null != $popup) {
			try {
				var offset = $popup.closest('div.ui-dialog').offset();
				var left = offset.left - $(document).scrollLeft();
				var top = offset.top - $(document).scrollTop();
				options.position = { 
					my: 'left top',
					at: 'left+' + left + ' top+' + top 
				};
			} catch(e) { }
			
		} else {
			options.position = {
				my: "center top",
				at: "center top+20"
			};
		}
		target = null;
		
	} else if(target && typeof target == "object" && null != target.my && null != target.at) {
		options.position = {
			my: target.my,
			at: target.at
		};
		
	} else {
		options.position = {
			my: "center top",
			at: "center top+20"
		};
	}
	
	// Reset (if exists)
	genericAjaxPopupDestroy($layer);
	
	if(null != width) options.width = width + 'px'; // [TODO] Fixed the forced 'px' later
	if(null != modal) options.modal = modal;
	
	// Load the popup content
	var $options = { async: false }
	genericAjaxGet('', request + '&layer=' + $layer,
		function(html) {
			$popup = $("#popup"+$layer);
			
			if(0 == $popup.length) {
				$("body").append("<div id='popup"+$layer+"' class='devblocks-popup' style='display:none;'></div>");
				$popup = $('#popup'+$layer);
			}

			// Persist
			genericAjaxPopupRegister($layer, $popup);
			
			// Target
			if(null != target && null == target.at) {
				options.position = {
					my: "right bottom",
					at: "left top",
					of: target
				};
			}
			
			// Max height
			//var max_height = Math.round($(window).height() * 0.85);
			//$popup.css('max-height', max_height + 'px');
			//options.maxHeight = max_height + 75;
			
			// Render
			$popup.dialog(options);
			
			// Open
			$popup.dialog('open');
			
			// Set the content
			$popup.html(html);
			
			if(null == options.position)
				$popup.dialog('option', 'position', { my: 'top', at: 'top+20px' } ); // { my: 'top center', at: 'center' }
			
			$popup.trigger('popup_open');
			
			// Callback
			try { cb(html); } catch(e) { }
		},
		$options
	);
	
	return $popup;
}

function genericAjaxPopupPostCloseReloadView($layer, frm, view_id, has_output, $event) {
	var has_view = (null != view_id && view_id.length > 0 && $('#view'+view_id).length > 0) ? true : false;
	if(null == has_output)
		has_output = false;

	if(has_view)
		$('#view'+view_id).fadeTo("fast", 0.2);
	
	genericAjaxPost(frm,view_id,'',
		function(html) {
			if(has_view && has_output) { // Reload from post content
				if(html.length > 0)
					$('#view'+view_id).html(html);
			} else if (has_view && !has_output) { // Reload from view_id
				genericAjaxGet('view'+view_id, 'c=internal&a=viewRefresh&id=' + view_id);
			}

			if(has_view)
				$('#view'+view_id).fadeTo("fast", 1.0);

			if(null == $layer) {
				$popup = genericAjaxPopupFind('#'+frm);
				if(null != $popup)
					$layer = $popup.attr('id').substring(5);
			} else {
				$popup = genericAjaxPopupFetch($layer);
			}
			
			if(null != $popup) {
				$popup.trigger('popup_saved');
				genericAjaxPopupClose($layer, $event);
			}
		}
	);
}

function genericAjaxGet(divRef,args,cb,options) {
	var div = null;

	// Polymorph div
	if(typeof divRef=="object")
		div = divRef;
	else if(typeof divRef=="string" && divRef.length > 0)
		div = $('#'+divRef);
	
	if(null == cb) {
		if(null != div)
			div.fadeTo("fast", 0.2);
		
		var cb = function(html) {
			if(null != div) {
				div.html(html);
				div.fadeTo("fast", 1.0);
				
				if(div.is('DIV[id^=view]'))
					div.trigger('view_refresh');
			}
		}
	}
	
	// Allow custom options
	if(null == options)
		options = { };
	
	options.type = 'GET';
	options.url = DevblocksAppPath+'ajax.php?'+args;
	options.cache = false;
	options.success = cb;
	
	$.ajax(options);
}

function genericAjaxPost(formRef,divRef,args,cb,options) {
	var frm = null;
	var div = null;
	
	// Polymorph form
	if(typeof formRef=="object")
		frm = formRef;
	else if(typeof formRef=="string" && formRef.length > 0)
		frm = $('#'+formRef);
	
	// Polymorph div
	if(typeof divRef=="object")
		div = divRef;
	else if(typeof divRef=="string" && divRef.length > 0)
		div = $('#'+divRef);
	
	if(null == frm)
		return;
	
	if(null == cb) {
		if(null != div)
			div.fadeTo("fast", 0.2);
		
		var cb = function(html) {
			if(null != div) {
				div.html(html);
				div.fadeTo("fast", 1.0);
				
				if(div.is('DIV[id^=view]'))
					div.trigger('view_refresh');
			}
		};
	}

	// Allow custom options
	if(null == options)
		options = { };
	
	options.type = 'POST';
	options.data = $(frm).serialize();
	options.url = DevblocksAppPath+'ajax.php'+(null!=args?('?'+args):''),
	options.cache = false;
	options.success = cb;
	
	$.ajax(options);
}

function devblocksAjaxDateChooser(field, div, options) {
	if(typeof field == 'object') {
		if(field.selector)
			var $sel_field = field;
		else
			var $sel_field = $(field);
	} else {
		var $sel_field = $(field);
	}
	
	if(typeof div == 'object') {
		if(div.selector)
			var $sel_div = div;
		else
			var $sel_div = $(div);
	} else {
		var $sel_div = $(div);
	}
	
	if(null == options)
		options = { 
			changeMonth: true,
			changeYear: true
		} ;
	
	if(null == options.dateFormat)
		options.dateFormat = 'DD, d MM yy';
			
	if(null == $sel_div) {
		var chooser = $sel_field.datepicker(options);
		
	} else {
		if(null == options.onSelect)
			options.onSelect = function(dateText, inst) {
				$sel_field.val(dateText);
				chooser.datepicker('destroy');
			};
		var chooser = $sel_div.datepicker(options);
	}
}
