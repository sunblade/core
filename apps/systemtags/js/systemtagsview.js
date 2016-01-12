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
				allowClear: true,
				multiple: true,
				query: _.bind(this._queryTagsAutocomplete, this),
				id: function(tag) {
					return tag.id;
				},
				initSelection: function(element, callback) {
					callback(self.collection.toJSON());
				},
				formatResult: function(tag) {
					return '<span>' + tag.name + '</span>';
				},
				formatSelection: function(tag) {
					return '<span>' + tag.name + '</span>';
				},
				createSearchChoice: function(term) {
					if (!self._newTag) {
						self._dummyId--;
						self._newTag = {
							id: self._dummyId,
							name: term
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
})();

