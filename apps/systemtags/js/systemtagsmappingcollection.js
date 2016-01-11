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
	/**
	 * @class OCA.SystemTags.SystemTagsCollection
	 * @classdesc
	 *
	 * Collection of tags assigned to a file
	 *
	 */
	var SystemTagsMappingCollection = OC.Backbone.Collection.extend(
		/** @lends OCA.SystemTags.SystemTagsCollection.prototype */ {

		sync: OC.Backbone.davSync,

		/**
		 * Id of the file for which to filter activities by
		 *
		 * @var int
		 */
		_objectId: null,

		/**
		 * Type of the object to filter by
		 *
		 * @var string
		 */
		_objectType: 'files',

		model: OCA.SystemTags.SystemTagModel,

		davProperties: OCA.SystemTags.SystemTagModel.prototype.davProperties,

		url: function() {
			return OC.linkToRemote('dav') + '/systemtags-relations/' + this._objectType + '/' + this._objectId;
		},

		/**
		 * Sets the object id to filter by or null for all.
		 * 
		 * @param {int} objectId file id or null
		 */
		setObjectId: function(objectId) {
			this._objectId = objectId;
		},

		/**
		 * Sets the object type to filter by or null for all.
		 * 
		 * @param {int} objectType file id or null
		 */
		setObjectType: function(objectType) {
			this._objectType = objectType;
		},

		initialize: function(models, options) {
			options = options || {};
			if (!_.isUndefined(options.objectId)) {
				this._objectId = options.objectId;
			}
			if (!_.isUndefined(options.objectType)) {
				this._objectType = options.objectType;
			}
		},

		getTagIds: function() {
			return this.map(function(model) {
				return model.id;
			});
		}
	});

	OCA.SystemTags.SystemTagsMappingCollection = SystemTagsMappingCollection;
})();

