/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global dav, Backbone */
if(!_.isUndefined(Backbone)) {
	OC.Backbone = Backbone.noConflict();

	(function() {
		var collectionMethodMap = {
			'create': 'POST',
			'update': 'PUT',
			'patch':  'PROPPATCH',
			'delete': 'DELETE',
			'read':   'PROPFIND'
		};
		var modelMethodMap = {
			'create': 'POST',
			'update': 'PUT',
			'patch':  'PROPPATCH',
			'delete': 'DELETE',
			'read':   'PROPFIND'
		};

		// Throw an error when a URL is needed, and none is supplied.
		var urlError = function() {
			throw new Error('A "url" property or function must be specified');
		};

		/**
		 * DAV transport
		 */
		OC.Backbone.davSync = function(method, model, options) {
			var params = {};
			var isCollection = (model instanceof OC.Backbone.Collection);

			if (isCollection) {
				params.type = options.type || collectionMethodMap[method];
			} else {
				params.type = options.type || modelMethodMap[method];
			}

			// Ensure that we have a URL.
			if (!options.url) {
				params.url = _.result(model, 'url') || urlError();
			}

			// Ensure that we have the appropriate request data.
			if (options.data == null && model && (method === 'create' || method === 'update' || method === 'patch')) {
				params.data = JSON.stringify(options.attrs || model.toJSON(options));
			}

			// Don't process data on a non-GET request.
			if (params.type !== 'PROPFIND') {
				params.processData = false;
			}

			if (params.type === 'PROPFIND') {
				if (model.davProperties) {
					if (_.isFunction(model.davProperties)) {
						params.davProperties = model.davProperties.call(model);
					} else {
						params.davProperties = model.davProperties;
					}
				}

				params.davProperties = _.extend(params.davProperties || {}, options.davProperties);

				if (_.isUndefined(options.depth)) {
					if (isCollection) {
						options.depth = 1;
					} else {
						options.depth = 0;
					}
				}
			}

			// Pass along `textStatus` and `errorThrown` from jQuery.
			var error = options.error;
			options.error = function(xhr, textStatus, errorThrown) {
				options.textStatus = textStatus;
				options.errorThrown = errorThrown;
				if (error) {
					error.call(options.context, xhr, textStatus, errorThrown);
				}
			};

			// Make the request, allowing the user to override any Ajax options.
			var xhr = options.xhr = OC.Backbone.davCall(_.extend(params, options), model);
			model.trigger('request', model, xhr, options);
			return xhr;
		};

		/**
		 * Convert a single propfind result to JSON
		 *
		 * @param {Object} result
		 * @param {Object} davProperties properties mapping
		 */
		var parsePropFindResult = function(result, davProperties) {
			var props = {
				href: result.href
			};

			_.each(result.propStat, function(propStat) {
				if (propStat.status !== 'HTTP/1.1 200 OK') {
					return;
				}

				for (var key in propStat.properties) {
					var propKey = key;
					if (davProperties[key]) {
						propKey = davProperties[key];
					}
					props[propKey] = propStat.properties[key];
				}
			});

			return props;
		};

		/**
		 * Parse ID from location
		 *
		 * @param {string} url url
		 * @return {string} id
		 */
		var parseIdFromLocation = function(url) {
			var queryPos = url.indexOf('?');
			if (queryPos > 0) {
				url = url.substr(0, queryPos);
			}

			var parts = url.split('/');
			return parts[parts.length - 1];
		};

		var isSuccessStatus = function(status) {
			return status >= 200 && status <= 299;
		};

		OC.Backbone.davCall = function(options, model) {
			var client = new dav.Client({
				baseUrl: options.url,
				xmlNamespaces: _.extend({
					'DAV:': 'd',
					'http://owncloud.org/ns': 'oc'
				}, options.xmlNamespaces || {})
			});
			client.resolveUrl = function() {
				return options.url;
			};
			var headers = _.extend({
				'X-Requested-With': 'XMLHttpRequest'
			}, options.headers);
			// TODO: add "X-Requested-With" "XMLHttpRequest" header
			if (options.type === 'PROPFIND') {
				client.propFind(
					options.url,
					_.values(options.davProperties) || [],
					options.depth
				).then(function(response) {
					if (isSuccessStatus(response.status)) {
						if (_.isFunction(options.success)) {
							var propsMapping = _.invert(options.davProperties);
							var results;
							if (options.depth > 0) {
								results = _.map(response.body, function(data) {
									return parsePropFindResult(data, propsMapping);
								});
								if (!options.includeRoot) {
									results.shift();
								}
							} else {
								results = parsePropFindResult(response.body, propsMapping);
							}

							options.success(results);
							return;
						}
					} else if (_.isFunction(options.error)) {
						options.error(response);
					}
				});
			} else {
				headers['Content-Type'] = 'application/json';
				client.request(
					options.type,
					options.url,
					headers,
					options.data
				).then(function(result) {
					if (isSuccessStatus(result.status)) {
						if (_.isFunction(options.success)) {
							if (options.type === 'PUT' || options.type === 'POST') {
								// pass the object's own values because the server
								// does not return anything
								var responseJson = result.body || model.toJSON();
								var locationHeader = result.xhr.getResponseHeader('Content-Location');
								if (options.type === 'POST' && locationHeader) {
									responseJson.id = parseIdFromLocation(locationHeader);
								}
								options.success(responseJson);
								return;
							}
							options.success(result.body);
						}
					} else if (_.isFunction(options.error)) {
						options.error(result);
					}
				});
			}
		};
	})();
}

