/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	var TEMPLATE =
		'<div class="systemTagsContainer">' +
		'<input type="hidden" name="tags" value="" style="width: 100%"/>' +
		'</div>';

	var RESULT_TEMPALTE =
		'<span {{#if isNew}}class="isNew"{{/if}}">' +
		'    <span class="checkmark icon icon-checkmark"></span>' +
		'    <span class="label">{{name}}</span>' +
		'    <span class="systemtags-actions">' +
		'        <span class="rename icon icon-rename"></span>' +
		'        <span class="delete icon icon-delete"></span>' +
		'    </span>' +
		'</span>';

	/**
	 * @class OCA.SystemTags.SystemTagsView
	 * @classdesc
	 *
	 * Displays a file's system tags
	 *
	 */
	var SystemTagsView = OCA.Files.DetailFileInfoView.extend(
		/** @lends OCA.SystemTags.SystemTagsView.prototype */ {

		_rendered: false,

		_newTag: null,

		_dummyId: -1,

		className: 'systemTagsView',

		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		},

		initialize: function(options) {
			options = options || {};

			this.completeCollection = new OCA.SystemTags.SystemTagsCollection();

			this.collection = new OCA.SystemTags.SystemTagsMappingCollection([], {objectType: 'files'});
			this.collection.on('sync', this._onTagsChanged, this);
			this.collection.on('remove', this._onTagsChanged, this);
		},

		setFileInfo: function(fileInfo) {
			if (!this._rendered) {
				this.render();
			}

			if (fileInfo) {
				this.collection.setObjectId(fileInfo.id);
				this.collection.fetch();
				this.$el.removeClass('hidden');
			} else {
				this.$el.addClass('hidden');
			}
		},

		_onTagsChanged: function() {
			this.$el.removeClass('hidden');
			this.$tagsField.select2('val', this.collection.getTagIds());
		},

		_queryTagsAutocomplete: function(query) {
			// TODO: set filter
			var self = this;
			if (this.completeCollection.fetched) {
				// cached
				query.callback({
					results: self.completeCollection.toJSON()
				});
				return;
			}

			this.completeCollection.fetch({
				success: function() {
					self.completeCollection.fetched = true;
					query.callback({
						results: self.completeCollection.toJSON()
					});
				}
			});
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			var self = this;
			this.$el.html(this.template({
				tags: this._tags
			}));

			this.$el.find('[title]').tooltip({placement: 'bottom'});
			this.$tagsField = this.$el.find('[name=tags]');
			this.$tagsField.select2({
				placeholder: t('files_external', 'Global tags'),
				containerCssClass: 'systemtags-select2-container',
				dropdownCssClass: 'systemtags-select2-dropdown',
				closeOnSelect: false,
				allowClear: false,
				multiple: true,
				query: _.bind(this._queryTagsAutocomplete, this),
				id: function(tag) {
					return tag.id;
				},
				initSelection: function(element, callback) {
					callback(self.collection.toJSON());
				},
				formatResult: function(tag) {
					// TODO: use handlebars
					return '<span>' +
						'    <span class="checkmark icon icon-checkmark"></span>' +
						'    <span class="label">' + escapeHTML(tag.name) + '</span>' +
						'    <span class="systemtags-actions">' +
						'        <span class="rename icon icon-rename"></span>' +
						'        <span class="delete icon icon-delete"></span>' +
						'    </span>' +
						'</span>';
				},
				formatSelection: function(tag) {
					return '<span>' + escapeHTML(tag.name) + ',&nbsp;</span>';
				},
				createSearchChoice: function(term) {
					if (!self._newTag) {
						self._dummyId--;
						self._newTag = {
							id: self._dummyId,
							name: term,
							isNew: true
						};
					} else {
						self._newTag.name = term;
					}

					return self._newTag;
				}
			}).on('select2-selecting', function(e) {
				if (e.object && e.object.id < 0) {
					// newly created tag, check if existing
					var existingTags = self.completeCollection.where({name: e.object.name});

					if (existingTags.length) {
						// create mapping to existing tag
						self.collection.create(existingTags[0].toJSON(), {
							error: function(model, response) {
								if (response.status === 409) {
									self._onTagsChanged();
									OC.Notification.showTemporary(t('core', 'Tag already exists'));
								}
							}
						});
					} else {
						// create a new mapping
						self.collection.create({
							name: e.object.name,
							userVisible: true,
							userAssignable: true,
						});
						self.completeCollection.fetched = false;
					}
				} else {
					// put the tag into the mapping collection
					self.collection.create(e.object);
				}
				self._newTag = null;
			}).on('select2-removing', function(e) {
				self.collection.get(e.choice.id).destroy();
			});

			this.delegateEvents();
		}
	});

	OCA.SystemTags.SystemTagsView = SystemTagsView;

	// FIXME HACK from http://stackoverflow.com/a/27466159
	// FIXME: move to some other place / make a plugin out of it
	Select2.class.multi.prototype.findHighlightableChoices = function () {
		return this.results.find(".select2-result-selectable:not(.select2-disabled)");
	};

	var Select2TriggerSelect = Select2.class.multi.prototype.triggerSelect;

	Select2.class.multi.prototype.triggerSelect = function (data) {
		if (this.val().indexOf(this.id(data)) !== -1) {

			var val = this.id(data);
			var evt = $.Event("select2-removing");
			evt.val = val;
			evt.choice = data;
			this.opts.element.trigger(evt);

			if (evt.isDefaultPrevented()) {
				return false;
			}

			var self = this;
			this.results.find('.select2-result.select2-selected').each(function () {
				var $this = $(this);
				if (self.id($this.data('select2-data')) === val) {
					$this.removeClass('select2-selected');
				}
			});

			this.opts.element.trigger({ type: "select2-removed", val: this.id(data), choice: data });
			this.triggerChange({ removed: data });

		} else {
			return Select2TriggerSelect.apply(this, arguments);
		}
	}

})();

