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
	var SystemTagsCollection = OC.Backbone.Collection.extend(
		/** @lends OCA.SystemTags.SystemTagsCollection.prototype */ {

		sync: OC.Backbone.davSync,

		model: OCA.SystemTags.SystemTagModel,

		davProperties: OCA.SystemTags.SystemTagModel.prototype.davProperties,

		url: function() {
			return OC.linkToRemote('dav') + '/systemtags/';
		}
	});

	OCA.SystemTags.SystemTagsCollection = SystemTagsCollection;
})();

