/*  Copyright 2011, MediaEvent Services GmbH & Co. KG
 *  This file is part of the LinkedData-Extension.
 *
 *   The LinkedData-Extension is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   The LinkedData-Extension is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
(function($){
	
	$.lodMapping = function(container) {
		var base = this;
		base.container = container;
		base.currentMapping = {
			mappingUri: null,
			source: null,
			target: null
		};
		base.mainContainer = null;
		base.serviceUrl = wgServer + ((wgScript == null) ? (wgScriptPath + "/index.php") : wgScript);		

		/**
		 * Loads the editor with a specific mapping
		 * @param mappingUri
		 * @param title
		 */
		base.openEditor = function(mapping, title) {
			base.currentMapping = mapping;
			base.overviewTable.hide();
			base.overviewButtonPane.hide();
			base.editorButtonPane.show();
			base.editor = new $.r2rEditor(base.mainContainer, {
				title: title,
				sourceUrl: base.serviceUrl + "?action=ajax&rs=lodGetR2RMapping&rsargs%5B%5D=" + escape(mapping.mappingUri),
				serialize: true,
				onCommit: function(data) {
					base.updateMapping(base.currentMapping, data);
				}
			});
		};
		
		base.closeEditor = function() {
			base.editor.remove();
			base.overviewTable.show();
			base.editorButtonPane.hide();
			base.overviewButtonPane.show();
		}

		/**
		 * Show the overview table
		 */
		base.init = function() {
			base.container.empty();
			$.r2rUI.showProgress();
			$.ajax({
				url: base.serviceUrl,
				data: {
					action: 'ajax',
					'rs': 'lodListR2RMappings',
				},
				dataType: 'json',
				success: function(data) {
					try {
						base.mainContainer = $("<div></div>").appendTo(base.container);
						base.overviewTable = $("<div>\
												<h1>All R2R Mappings</h1>\
												<div class=\"ui-tabs ui-widget ui-widget-content ui-corner-all\">\
												<table id=\"lodmapping-table\" class=\"r2redit-mappingTable\">\
												<thead class=\"ui-widget-header\">\
													<tr>\
														<th>ID</th>\
														<th>From</th>\
														<th>To</th>\
														<th>Edit</th>\
													</tr>\
													<tbody class=\"ui-widget-content\">\
													</tbody>\
												</thead>\
												</table>\
												</div>\
											</div>").appendTo(base.mainContainer);
						base.overviewButtonPane = $("<div></div>")
											.addClass("lodmapping-buttonpane")
											.appendTo(base.container);
						base.newMappingButton = 
							$("<div></div>").button({
													icons: {
														primary: "ui-icon-add"
													},
													label: "New R2R Mapping",
											})
											.click(function() {
												base.newMappingDialog();
											})
											.appendTo(base.overviewButtonPane);
											
						base.editorButtonPane = $("<div></div>")
											.addClass("lodmapping-buttonpane")
											.appendTo(base.container)
											.hide();
											
						base.backButton = 
							$("<div></div>").button({
													icons: {
														primary: "ui-icon-back"
													},
													label: "Back to Overview",
											})
											.click(function() {
												base.closeEditor();
											})
											.appendTo(base.editorButtonPane);
						base.removeButton = 
							$("<div></div>").button({
													label: "Remove Mapping",
											})
											.click(function() {
												var dialog = $("<div class=\"r2redit-dialog\" title=\"Remove Mapping\">\
																<p>\
																<span class=\"ui-icon ui-icon-alert\"></span>\
																Are you sure?\
																</p>\
																</div>");
												dialog.dialog({
													autoOpen: true,
													height: 150,
													width: 300,
													modal: true,
													buttons: {
														"Remove": function() {
															/**
															 * Workaround: This gets called once on initialization... seems to be a jQuery UI bug
															 */
															if (!dialogOpened) {
																return;
															}
															$(this).dialog("close");
															base.closeEditor();
															base.removeMapping(base.currentMapping);
														},
														"Cancel": function() {
															/**
															 * Workaround: This gets called once on initialization... seems to be a jQuery UI bug
															 */
															if (!dialogOpened) {
																return;
															}
															$(this).dialog("close");
														}
													}
												});
												var dialogOpened = true;
												$.r2rUI.fixJQueryUIDialogButtons(dialog);
											})
											.appendTo(base.editorButtonPane);
											
						var overviewTableBody = base.overviewTable.find("tbody");
						$.each(data, function(key, mapping) {
							$("<tr></tr>")
								/* ID */
								.append(
									$("<td>" + mapping.id + "</td>")
									.addClass("r2redit-mappingTableProperty")
									.addClass("r2redit-mappingTableName")
								)
								/* From */
								.append(
									$("<td>" + mapping.source + "</td>")
									.addClass("r2redit-mappingTableProperty")
								)
								/* To */
								.append(
									$("<td>" + mapping.target + "</td>")
									.addClass("r2redit-mappingTableProperty")
								)
								/* Edit */
								.append(
									$("<td></td>")
										.addClass("r2redit-mappingTableAction")
										.addClass("r2redit-mappingTableClickable")
										.addClass("r2redit-mappingTableEdit")
										.click(function() {
											base.openEditor({mappingUri: mapping.uri, source: mapping.source, target: mapping.target}, mapping.id); 
										})
								)
								/* Remove */
								.appendTo(overviewTableBody);
						});
					} catch (err) {
						$.r2rUI.showError("R2Redit error", err);
					}
		   		},
		        error: function(jqXHR, textStatus, err) {
					$.r2rUI.showError("Unable to load mapping list", err);        	
		        },
		        complete: function() {
     					$.r2rUI.hideProgress();
		        }
			});
		};
		
		base.newMappingDialog = function() {
		};
		
		/**
		 * Updates the specified mapping
		 * @param mapping
		 * @param ttl
		 */
		base.updateMapping = function(mapping, ttl) {
			base.invokeMethod("lodUpdateR2RMapping", [mapping.mappingUri, mapping.source, mapping.target, ttl], "Unable to update mapping");
		};

		/**
		 * Removes the specified mapping
		 * @param mapping
		 */
		base.removeMapping = function(mapping) {
			base.invokeMethod("lodRemoveR2RMapping", [mapping.mappingUri], "Unable to remove mapping", function() { base.init(); });
		};
		
		/**
		 * Invokes an RPC method
		 * @param method
		 * @param parameters
		 * @param errorTitle
		 * @param onComplete
		 */
		base.invokeMethod = function(method, parameters, errorTitle, onComplete) {
			$.r2rUI.showProgress();
			$.ajax({
				url: base.serviceUrl,
				data: {
					action: 'ajax',
					'rs': method,
					'rsargs[]': parameters
				},
				dataType: 'text',
				type: 'post',
		        error: function(jqXHR, textStatus, err) {
					$.r2rUI.showError(errorTitle, err);        	
		        },
		        complete: function() {
   					$.r2rUI.hideProgress();
   					if (onComplete) {
   						onComplete();
   					}
		        }
			});
		};		
		
		base.init();
		return base;
	};		

})(jQuery);