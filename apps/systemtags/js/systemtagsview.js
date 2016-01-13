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

	var RESULT_TEMPLATE =
		'<span class="systemtags-item{{#if isNew}} new-item{{/if}}" data-id="{{id}}">' +
		'    <span class="checkmark icon icon-checkmark"></span>' +
		'    <span class="label">{{name}}</span>' +
		'    <span class="systemtags-actions">' +
		'        <a href="#" class="rename icon icon-rename"></a>' +
		'        <a href="#" class="delete icon icon-delete"></a>' +
		'    </span>' +
		'</span>';

	function convertResult(model) {
		return model.toJSON();
	}

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

			_.bindAll(this, '_onClickRenameTag', '_onClickDeleteTag', '_onSelectTag', '_onDeselectTag', '_onOpenDropDown');
		},

		_onClickRenameTag: function(ev) {
			var $item = $(ev.target).closest('.systemtags-item');
			var tagId = $item.attr('data-id');
			var tagModel = this.completeCollection.get(tagId);
			// TODO: replace with inline field
			var newName = prompt('Rename tag', tagModel.get('name'));
			if (newName && newName !== tagModel.get('name')) {
				tagModel.save({'name': newName});
				// TODO: spinner, and only change text after finished saving
				$item.find('.label').text(newName);
			}
			return false;
		},

		_onClickDeleteTag: function(ev) {
			var $item = $(ev.target).closest('.systemtags-item');
			var tagId = $item.attr('data-id');
			this.completeCollection.get(tagId).destroy();
			this.collection.remove(tagId);
			$item.closest('.select2-result').remove();
			// TODO: spinner
			return false;
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

		_onSelectTag: function(e) {
			var self = this;
			if (e.object && e.object.id < 0) {
				// newly created tag, check if existing
				var existingTags = this.completeCollection.where({name: e.object.name});

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
					this.collection.create({
						name: e.object.name,
						userVisible: true,
						userAssignable: true,
					});
					this.completeCollection.fetched = false;
				}
			} else {
				// put the tag into the mapping collection
				this.collection.create(e.object);
			}
			this._newTag = null;
		},

		_queryTagsAutocomplete: function(query) {
			var self = this;
			if (this.completeCollection.fetched) {
				// cached
				query.callback({
					results: _.map(self.completeCollection.filterByName(query.term), convertResult)
				});
				return;
			}

			this.completeCollection.fetch({
				success: function() {
					self.completeCollection.fetched = true;
					query.callback({
						results: _.map(self.completeCollection.filterByName(query.term), convertResult)
					});
				}
			});
		},

		_onDeselectTag: function(e) {
			this.collection.get(e.choice.id).destroy();
		},

		_onOpenDropDown: function(e) {
			var $dropDown = $(e.target).select2('dropdown');
			// register events for inside the dropdown
			$dropDown.on('mouseup', '.rename', this._onClickRenameTag);
			$dropDown.on('mouseup', '.delete', this._onClickDeleteTag);
		},

		_formatDropDownResult: function(data) {
			if (!this._resultTemplate) {
				this._resultTemplate = Handlebars.compile(RESULT_TEMPLATE);
			}
			return this._resultTemplate(data);
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
				placeholder: t('core', 'Global tags'),
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
				formatResult: _.bind(this._formatDropDownResult, this),
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
			})
				.on('select2-selecting', this._onSelectTag)
				.on('select2-removing', this._onDeselectTag)
				.on('select2-open', this._onOpenDropDown);

			this.delegateEvents();
		},

		remove: function() {
			this.$tagsField.select2('destroy');
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

