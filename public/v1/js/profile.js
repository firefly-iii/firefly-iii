/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 4);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/axios/index.js":
/*!*************************************!*\
  !*** ./node_modules/axios/index.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! ./lib/axios */ "./node_modules/axios/lib/axios.js");

/***/ }),

/***/ "./node_modules/axios/lib/adapters/xhr.js":
/*!************************************************!*\
  !*** ./node_modules/axios/lib/adapters/xhr.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var settle = __webpack_require__(/*! ./../core/settle */ "./node_modules/axios/lib/core/settle.js");
var buildURL = __webpack_require__(/*! ./../helpers/buildURL */ "./node_modules/axios/lib/helpers/buildURL.js");
var parseHeaders = __webpack_require__(/*! ./../helpers/parseHeaders */ "./node_modules/axios/lib/helpers/parseHeaders.js");
var isURLSameOrigin = __webpack_require__(/*! ./../helpers/isURLSameOrigin */ "./node_modules/axios/lib/helpers/isURLSameOrigin.js");
var createError = __webpack_require__(/*! ../core/createError */ "./node_modules/axios/lib/core/createError.js");

module.exports = function xhrAdapter(config) {
  return new Promise(function dispatchXhrRequest(resolve, reject) {
    var requestData = config.data;
    var requestHeaders = config.headers;

    if (utils.isFormData(requestData)) {
      delete requestHeaders['Content-Type']; // Let the browser set it
    }

    var request = new XMLHttpRequest();

    // HTTP basic authentication
    if (config.auth) {
      var username = config.auth.username || '';
      var password = config.auth.password || '';
      requestHeaders.Authorization = 'Basic ' + btoa(username + ':' + password);
    }

    request.open(config.method.toUpperCase(), buildURL(config.url, config.params, config.paramsSerializer), true);

    // Set the request timeout in MS
    request.timeout = config.timeout;

    // Listen for ready state
    request.onreadystatechange = function handleLoad() {
      if (!request || request.readyState !== 4) {
        return;
      }

      // The request errored out and we didn't get a response, this will be
      // handled by onerror instead
      // With one exception: request that using file: protocol, most browsers
      // will return status as 0 even though it's a successful request
      if (request.status === 0 && !(request.responseURL && request.responseURL.indexOf('file:') === 0)) {
        return;
      }

      // Prepare the response
      var responseHeaders = 'getAllResponseHeaders' in request ? parseHeaders(request.getAllResponseHeaders()) : null;
      var responseData = !config.responseType || config.responseType === 'text' ? request.responseText : request.response;
      var response = {
        data: responseData,
        status: request.status,
        statusText: request.statusText,
        headers: responseHeaders,
        config: config,
        request: request
      };

      settle(resolve, reject, response);

      // Clean up request
      request = null;
    };

    // Handle low level network errors
    request.onerror = function handleError() {
      // Real errors are hidden from us by the browser
      // onerror should only fire if it's a network error
      reject(createError('Network Error', config, null, request));

      // Clean up request
      request = null;
    };

    // Handle timeout
    request.ontimeout = function handleTimeout() {
      reject(createError('timeout of ' + config.timeout + 'ms exceeded', config, 'ECONNABORTED',
        request));

      // Clean up request
      request = null;
    };

    // Add xsrf header
    // This is only done if running in a standard browser environment.
    // Specifically not if we're in a web worker, or react-native.
    if (utils.isStandardBrowserEnv()) {
      var cookies = __webpack_require__(/*! ./../helpers/cookies */ "./node_modules/axios/lib/helpers/cookies.js");

      // Add xsrf header
      var xsrfValue = (config.withCredentials || isURLSameOrigin(config.url)) && config.xsrfCookieName ?
          cookies.read(config.xsrfCookieName) :
          undefined;

      if (xsrfValue) {
        requestHeaders[config.xsrfHeaderName] = xsrfValue;
      }
    }

    // Add headers to the request
    if ('setRequestHeader' in request) {
      utils.forEach(requestHeaders, function setRequestHeader(val, key) {
        if (typeof requestData === 'undefined' && key.toLowerCase() === 'content-type') {
          // Remove Content-Type if data is undefined
          delete requestHeaders[key];
        } else {
          // Otherwise add header to the request
          request.setRequestHeader(key, val);
        }
      });
    }

    // Add withCredentials to request if needed
    if (config.withCredentials) {
      request.withCredentials = true;
    }

    // Add responseType to request if needed
    if (config.responseType) {
      try {
        request.responseType = config.responseType;
      } catch (e) {
        // Expected DOMException thrown by browsers not compatible XMLHttpRequest Level 2.
        // But, this can be suppressed for 'json' type as it can be parsed by default 'transformResponse' function.
        if (config.responseType !== 'json') {
          throw e;
        }
      }
    }

    // Handle progress if needed
    if (typeof config.onDownloadProgress === 'function') {
      request.addEventListener('progress', config.onDownloadProgress);
    }

    // Not all browsers support upload events
    if (typeof config.onUploadProgress === 'function' && request.upload) {
      request.upload.addEventListener('progress', config.onUploadProgress);
    }

    if (config.cancelToken) {
      // Handle cancellation
      config.cancelToken.promise.then(function onCanceled(cancel) {
        if (!request) {
          return;
        }

        request.abort();
        reject(cancel);
        // Clean up request
        request = null;
      });
    }

    if (requestData === undefined) {
      requestData = null;
    }

    // Send the request
    request.send(requestData);
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/axios.js":
/*!*****************************************!*\
  !*** ./node_modules/axios/lib/axios.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./utils */ "./node_modules/axios/lib/utils.js");
var bind = __webpack_require__(/*! ./helpers/bind */ "./node_modules/axios/lib/helpers/bind.js");
var Axios = __webpack_require__(/*! ./core/Axios */ "./node_modules/axios/lib/core/Axios.js");
var defaults = __webpack_require__(/*! ./defaults */ "./node_modules/axios/lib/defaults.js");

/**
 * Create an instance of Axios
 *
 * @param {Object} defaultConfig The default config for the instance
 * @return {Axios} A new instance of Axios
 */
function createInstance(defaultConfig) {
  var context = new Axios(defaultConfig);
  var instance = bind(Axios.prototype.request, context);

  // Copy axios.prototype to instance
  utils.extend(instance, Axios.prototype, context);

  // Copy context to instance
  utils.extend(instance, context);

  return instance;
}

// Create the default instance to be exported
var axios = createInstance(defaults);

// Expose Axios class to allow class inheritance
axios.Axios = Axios;

// Factory for creating new instances
axios.create = function create(instanceConfig) {
  return createInstance(utils.merge(defaults, instanceConfig));
};

// Expose Cancel & CancelToken
axios.Cancel = __webpack_require__(/*! ./cancel/Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");
axios.CancelToken = __webpack_require__(/*! ./cancel/CancelToken */ "./node_modules/axios/lib/cancel/CancelToken.js");
axios.isCancel = __webpack_require__(/*! ./cancel/isCancel */ "./node_modules/axios/lib/cancel/isCancel.js");

// Expose all/spread
axios.all = function all(promises) {
  return Promise.all(promises);
};
axios.spread = __webpack_require__(/*! ./helpers/spread */ "./node_modules/axios/lib/helpers/spread.js");

module.exports = axios;

// Allow use of default import syntax in TypeScript
module.exports.default = axios;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/Cancel.js":
/*!*************************************************!*\
  !*** ./node_modules/axios/lib/cancel/Cancel.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * A `Cancel` is an object that is thrown when an operation is canceled.
 *
 * @class
 * @param {string=} message The message.
 */
function Cancel(message) {
  this.message = message;
}

Cancel.prototype.toString = function toString() {
  return 'Cancel' + (this.message ? ': ' + this.message : '');
};

Cancel.prototype.__CANCEL__ = true;

module.exports = Cancel;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/CancelToken.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/cancel/CancelToken.js ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Cancel = __webpack_require__(/*! ./Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");

/**
 * A `CancelToken` is an object that can be used to request cancellation of an operation.
 *
 * @class
 * @param {Function} executor The executor function.
 */
function CancelToken(executor) {
  if (typeof executor !== 'function') {
    throw new TypeError('executor must be a function.');
  }

  var resolvePromise;
  this.promise = new Promise(function promiseExecutor(resolve) {
    resolvePromise = resolve;
  });

  var token = this;
  executor(function cancel(message) {
    if (token.reason) {
      // Cancellation has already been requested
      return;
    }

    token.reason = new Cancel(message);
    resolvePromise(token.reason);
  });
}

/**
 * Throws a `Cancel` if cancellation has been requested.
 */
CancelToken.prototype.throwIfRequested = function throwIfRequested() {
  if (this.reason) {
    throw this.reason;
  }
};

/**
 * Returns an object that contains a new `CancelToken` and a function that, when called,
 * cancels the `CancelToken`.
 */
CancelToken.source = function source() {
  var cancel;
  var token = new CancelToken(function executor(c) {
    cancel = c;
  });
  return {
    token: token,
    cancel: cancel
  };
};

module.exports = CancelToken;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/isCancel.js":
/*!***************************************************!*\
  !*** ./node_modules/axios/lib/cancel/isCancel.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = function isCancel(value) {
  return !!(value && value.__CANCEL__);
};


/***/ }),

/***/ "./node_modules/axios/lib/core/Axios.js":
/*!**********************************************!*\
  !*** ./node_modules/axios/lib/core/Axios.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var defaults = __webpack_require__(/*! ./../defaults */ "./node_modules/axios/lib/defaults.js");
var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var InterceptorManager = __webpack_require__(/*! ./InterceptorManager */ "./node_modules/axios/lib/core/InterceptorManager.js");
var dispatchRequest = __webpack_require__(/*! ./dispatchRequest */ "./node_modules/axios/lib/core/dispatchRequest.js");

/**
 * Create a new instance of Axios
 *
 * @param {Object} instanceConfig The default config for the instance
 */
function Axios(instanceConfig) {
  this.defaults = instanceConfig;
  this.interceptors = {
    request: new InterceptorManager(),
    response: new InterceptorManager()
  };
}

/**
 * Dispatch a request
 *
 * @param {Object} config The config specific for this request (merged with this.defaults)
 */
Axios.prototype.request = function request(config) {
  /*eslint no-param-reassign:0*/
  // Allow for axios('example/url'[, config]) a la fetch API
  if (typeof config === 'string') {
    config = utils.merge({
      url: arguments[0]
    }, arguments[1]);
  }

  config = utils.merge(defaults, {method: 'get'}, this.defaults, config);
  config.method = config.method.toLowerCase();

  // Hook up interceptors middleware
  var chain = [dispatchRequest, undefined];
  var promise = Promise.resolve(config);

  this.interceptors.request.forEach(function unshiftRequestInterceptors(interceptor) {
    chain.unshift(interceptor.fulfilled, interceptor.rejected);
  });

  this.interceptors.response.forEach(function pushResponseInterceptors(interceptor) {
    chain.push(interceptor.fulfilled, interceptor.rejected);
  });

  while (chain.length) {
    promise = promise.then(chain.shift(), chain.shift());
  }

  return promise;
};

// Provide aliases for supported request methods
utils.forEach(['delete', 'get', 'head', 'options'], function forEachMethodNoData(method) {
  /*eslint func-names:0*/
  Axios.prototype[method] = function(url, config) {
    return this.request(utils.merge(config || {}, {
      method: method,
      url: url
    }));
  };
});

utils.forEach(['post', 'put', 'patch'], function forEachMethodWithData(method) {
  /*eslint func-names:0*/
  Axios.prototype[method] = function(url, data, config) {
    return this.request(utils.merge(config || {}, {
      method: method,
      url: url,
      data: data
    }));
  };
});

module.exports = Axios;


/***/ }),

/***/ "./node_modules/axios/lib/core/InterceptorManager.js":
/*!***********************************************************!*\
  !*** ./node_modules/axios/lib/core/InterceptorManager.js ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

function InterceptorManager() {
  this.handlers = [];
}

/**
 * Add a new interceptor to the stack
 *
 * @param {Function} fulfilled The function to handle `then` for a `Promise`
 * @param {Function} rejected The function to handle `reject` for a `Promise`
 *
 * @return {Number} An ID used to remove interceptor later
 */
InterceptorManager.prototype.use = function use(fulfilled, rejected) {
  this.handlers.push({
    fulfilled: fulfilled,
    rejected: rejected
  });
  return this.handlers.length - 1;
};

/**
 * Remove an interceptor from the stack
 *
 * @param {Number} id The ID that was returned by `use`
 */
InterceptorManager.prototype.eject = function eject(id) {
  if (this.handlers[id]) {
    this.handlers[id] = null;
  }
};

/**
 * Iterate over all the registered interceptors
 *
 * This method is particularly useful for skipping over any
 * interceptors that may have become `null` calling `eject`.
 *
 * @param {Function} fn The function to call for each interceptor
 */
InterceptorManager.prototype.forEach = function forEach(fn) {
  utils.forEach(this.handlers, function forEachHandler(h) {
    if (h !== null) {
      fn(h);
    }
  });
};

module.exports = InterceptorManager;


/***/ }),

/***/ "./node_modules/axios/lib/core/createError.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/core/createError.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var enhanceError = __webpack_require__(/*! ./enhanceError */ "./node_modules/axios/lib/core/enhanceError.js");

/**
 * Create an Error with the specified message, config, error code, request and response.
 *
 * @param {string} message The error message.
 * @param {Object} config The config.
 * @param {string} [code] The error code (for example, 'ECONNABORTED').
 * @param {Object} [request] The request.
 * @param {Object} [response] The response.
 * @returns {Error} The created error.
 */
module.exports = function createError(message, config, code, request, response) {
  var error = new Error(message);
  return enhanceError(error, config, code, request, response);
};


/***/ }),

/***/ "./node_modules/axios/lib/core/dispatchRequest.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/core/dispatchRequest.js ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var transformData = __webpack_require__(/*! ./transformData */ "./node_modules/axios/lib/core/transformData.js");
var isCancel = __webpack_require__(/*! ../cancel/isCancel */ "./node_modules/axios/lib/cancel/isCancel.js");
var defaults = __webpack_require__(/*! ../defaults */ "./node_modules/axios/lib/defaults.js");
var isAbsoluteURL = __webpack_require__(/*! ./../helpers/isAbsoluteURL */ "./node_modules/axios/lib/helpers/isAbsoluteURL.js");
var combineURLs = __webpack_require__(/*! ./../helpers/combineURLs */ "./node_modules/axios/lib/helpers/combineURLs.js");

/**
 * Throws a `Cancel` if cancellation has been requested.
 */
function throwIfCancellationRequested(config) {
  if (config.cancelToken) {
    config.cancelToken.throwIfRequested();
  }
}

/**
 * Dispatch a request to the server using the configured adapter.
 *
 * @param {object} config The config that is to be used for the request
 * @returns {Promise} The Promise to be fulfilled
 */
module.exports = function dispatchRequest(config) {
  throwIfCancellationRequested(config);

  // Support baseURL config
  if (config.baseURL && !isAbsoluteURL(config.url)) {
    config.url = combineURLs(config.baseURL, config.url);
  }

  // Ensure headers exist
  config.headers = config.headers || {};

  // Transform request data
  config.data = transformData(
    config.data,
    config.headers,
    config.transformRequest
  );

  // Flatten headers
  config.headers = utils.merge(
    config.headers.common || {},
    config.headers[config.method] || {},
    config.headers || {}
  );

  utils.forEach(
    ['delete', 'get', 'head', 'post', 'put', 'patch', 'common'],
    function cleanHeaderConfig(method) {
      delete config.headers[method];
    }
  );

  var adapter = config.adapter || defaults.adapter;

  return adapter(config).then(function onAdapterResolution(response) {
    throwIfCancellationRequested(config);

    // Transform response data
    response.data = transformData(
      response.data,
      response.headers,
      config.transformResponse
    );

    return response;
  }, function onAdapterRejection(reason) {
    if (!isCancel(reason)) {
      throwIfCancellationRequested(config);

      // Transform response data
      if (reason && reason.response) {
        reason.response.data = transformData(
          reason.response.data,
          reason.response.headers,
          config.transformResponse
        );
      }
    }

    return Promise.reject(reason);
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/core/enhanceError.js":
/*!*****************************************************!*\
  !*** ./node_modules/axios/lib/core/enhanceError.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Update an Error with the specified config, error code, and response.
 *
 * @param {Error} error The error to update.
 * @param {Object} config The config.
 * @param {string} [code] The error code (for example, 'ECONNABORTED').
 * @param {Object} [request] The request.
 * @param {Object} [response] The response.
 * @returns {Error} The error.
 */
module.exports = function enhanceError(error, config, code, request, response) {
  error.config = config;
  if (code) {
    error.code = code;
  }
  error.request = request;
  error.response = response;
  return error;
};


/***/ }),

/***/ "./node_modules/axios/lib/core/settle.js":
/*!***********************************************!*\
  !*** ./node_modules/axios/lib/core/settle.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var createError = __webpack_require__(/*! ./createError */ "./node_modules/axios/lib/core/createError.js");

/**
 * Resolve or reject a Promise based on response status.
 *
 * @param {Function} resolve A function that resolves the promise.
 * @param {Function} reject A function that rejects the promise.
 * @param {object} response The response.
 */
module.exports = function settle(resolve, reject, response) {
  var validateStatus = response.config.validateStatus;
  // Note: status is not exposed by XDomainRequest
  if (!response.status || !validateStatus || validateStatus(response.status)) {
    resolve(response);
  } else {
    reject(createError(
      'Request failed with status code ' + response.status,
      response.config,
      null,
      response.request,
      response
    ));
  }
};


/***/ }),

/***/ "./node_modules/axios/lib/core/transformData.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/core/transformData.js ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

/**
 * Transform the data for a request or a response
 *
 * @param {Object|String} data The data to be transformed
 * @param {Array} headers The headers for the request or response
 * @param {Array|Function} fns A single function or Array of functions
 * @returns {*} The resulting transformed data
 */
module.exports = function transformData(data, headers, fns) {
  /*eslint no-param-reassign:0*/
  utils.forEach(fns, function transform(fn) {
    data = fn(data, headers);
  });

  return data;
};


/***/ }),

/***/ "./node_modules/axios/lib/defaults.js":
/*!********************************************!*\
  !*** ./node_modules/axios/lib/defaults.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(process) {

var utils = __webpack_require__(/*! ./utils */ "./node_modules/axios/lib/utils.js");
var normalizeHeaderName = __webpack_require__(/*! ./helpers/normalizeHeaderName */ "./node_modules/axios/lib/helpers/normalizeHeaderName.js");

var DEFAULT_CONTENT_TYPE = {
  'Content-Type': 'application/x-www-form-urlencoded'
};

function setContentTypeIfUnset(headers, value) {
  if (!utils.isUndefined(headers) && utils.isUndefined(headers['Content-Type'])) {
    headers['Content-Type'] = value;
  }
}

function getDefaultAdapter() {
  var adapter;
  if (typeof XMLHttpRequest !== 'undefined') {
    // For browsers use XHR adapter
    adapter = __webpack_require__(/*! ./adapters/xhr */ "./node_modules/axios/lib/adapters/xhr.js");
  } else if (typeof process !== 'undefined') {
    // For node use HTTP adapter
    adapter = __webpack_require__(/*! ./adapters/http */ "./node_modules/axios/lib/adapters/xhr.js");
  }
  return adapter;
}

var defaults = {
  adapter: getDefaultAdapter(),

  transformRequest: [function transformRequest(data, headers) {
    normalizeHeaderName(headers, 'Content-Type');
    if (utils.isFormData(data) ||
      utils.isArrayBuffer(data) ||
      utils.isBuffer(data) ||
      utils.isStream(data) ||
      utils.isFile(data) ||
      utils.isBlob(data)
    ) {
      return data;
    }
    if (utils.isArrayBufferView(data)) {
      return data.buffer;
    }
    if (utils.isURLSearchParams(data)) {
      setContentTypeIfUnset(headers, 'application/x-www-form-urlencoded;charset=utf-8');
      return data.toString();
    }
    if (utils.isObject(data)) {
      setContentTypeIfUnset(headers, 'application/json;charset=utf-8');
      return JSON.stringify(data);
    }
    return data;
  }],

  transformResponse: [function transformResponse(data) {
    /*eslint no-param-reassign:0*/
    if (typeof data === 'string') {
      try {
        data = JSON.parse(data);
      } catch (e) { /* Ignore */ }
    }
    return data;
  }],

  /**
   * A timeout in milliseconds to abort a request. If set to 0 (default) a
   * timeout is not created.
   */
  timeout: 0,

  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',

  maxContentLength: -1,

  validateStatus: function validateStatus(status) {
    return status >= 200 && status < 300;
  }
};

defaults.headers = {
  common: {
    'Accept': 'application/json, text/plain, */*'
  }
};

utils.forEach(['delete', 'get', 'head'], function forEachMethodNoData(method) {
  defaults.headers[method] = {};
});

utils.forEach(['post', 'put', 'patch'], function forEachMethodWithData(method) {
  defaults.headers[method] = utils.merge(DEFAULT_CONTENT_TYPE);
});

module.exports = defaults;

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../process/browser.js */ "./node_modules/process/browser.js")))

/***/ }),

/***/ "./node_modules/axios/lib/helpers/bind.js":
/*!************************************************!*\
  !*** ./node_modules/axios/lib/helpers/bind.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = function bind(fn, thisArg) {
  return function wrap() {
    var args = new Array(arguments.length);
    for (var i = 0; i < args.length; i++) {
      args[i] = arguments[i];
    }
    return fn.apply(thisArg, args);
  };
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/buildURL.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/helpers/buildURL.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

function encode(val) {
  return encodeURIComponent(val).
    replace(/%40/gi, '@').
    replace(/%3A/gi, ':').
    replace(/%24/g, '$').
    replace(/%2C/gi, ',').
    replace(/%20/g, '+').
    replace(/%5B/gi, '[').
    replace(/%5D/gi, ']');
}

/**
 * Build a URL by appending params to the end
 *
 * @param {string} url The base of the url (e.g., http://www.google.com)
 * @param {object} [params] The params to be appended
 * @returns {string} The formatted url
 */
module.exports = function buildURL(url, params, paramsSerializer) {
  /*eslint no-param-reassign:0*/
  if (!params) {
    return url;
  }

  var serializedParams;
  if (paramsSerializer) {
    serializedParams = paramsSerializer(params);
  } else if (utils.isURLSearchParams(params)) {
    serializedParams = params.toString();
  } else {
    var parts = [];

    utils.forEach(params, function serialize(val, key) {
      if (val === null || typeof val === 'undefined') {
        return;
      }

      if (utils.isArray(val)) {
        key = key + '[]';
      } else {
        val = [val];
      }

      utils.forEach(val, function parseValue(v) {
        if (utils.isDate(v)) {
          v = v.toISOString();
        } else if (utils.isObject(v)) {
          v = JSON.stringify(v);
        }
        parts.push(encode(key) + '=' + encode(v));
      });
    });

    serializedParams = parts.join('&');
  }

  if (serializedParams) {
    url += (url.indexOf('?') === -1 ? '?' : '&') + serializedParams;
  }

  return url;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/combineURLs.js":
/*!*******************************************************!*\
  !*** ./node_modules/axios/lib/helpers/combineURLs.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Creates a new URL by combining the specified URLs
 *
 * @param {string} baseURL The base URL
 * @param {string} relativeURL The relative URL
 * @returns {string} The combined URL
 */
module.exports = function combineURLs(baseURL, relativeURL) {
  return relativeURL
    ? baseURL.replace(/\/+$/, '') + '/' + relativeURL.replace(/^\/+/, '')
    : baseURL;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/cookies.js":
/*!***************************************************!*\
  !*** ./node_modules/axios/lib/helpers/cookies.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

module.exports = (
  utils.isStandardBrowserEnv() ?

  // Standard browser envs support document.cookie
  (function standardBrowserEnv() {
    return {
      write: function write(name, value, expires, path, domain, secure) {
        var cookie = [];
        cookie.push(name + '=' + encodeURIComponent(value));

        if (utils.isNumber(expires)) {
          cookie.push('expires=' + new Date(expires).toGMTString());
        }

        if (utils.isString(path)) {
          cookie.push('path=' + path);
        }

        if (utils.isString(domain)) {
          cookie.push('domain=' + domain);
        }

        if (secure === true) {
          cookie.push('secure');
        }

        document.cookie = cookie.join('; ');
      },

      read: function read(name) {
        var match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
        return (match ? decodeURIComponent(match[3]) : null);
      },

      remove: function remove(name) {
        this.write(name, '', Date.now() - 86400000);
      }
    };
  })() :

  // Non standard browser env (web workers, react-native) lack needed support.
  (function nonStandardBrowserEnv() {
    return {
      write: function write() {},
      read: function read() { return null; },
      remove: function remove() {}
    };
  })()
);


/***/ }),

/***/ "./node_modules/axios/lib/helpers/isAbsoluteURL.js":
/*!*********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isAbsoluteURL.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Determines whether the specified URL is absolute
 *
 * @param {string} url The URL to test
 * @returns {boolean} True if the specified URL is absolute, otherwise false
 */
module.exports = function isAbsoluteURL(url) {
  // A URL is considered absolute if it begins with "<scheme>://" or "//" (protocol-relative URL).
  // RFC 3986 defines scheme name as a sequence of characters beginning with a letter and followed
  // by any combination of letters, digits, plus, period, or hyphen.
  return /^([a-z][a-z\d\+\-\.]*:)?\/\//i.test(url);
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/isURLSameOrigin.js":
/*!***********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isURLSameOrigin.js ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

module.exports = (
  utils.isStandardBrowserEnv() ?

  // Standard browser envs have full support of the APIs needed to test
  // whether the request URL is of the same origin as current location.
  (function standardBrowserEnv() {
    var msie = /(msie|trident)/i.test(navigator.userAgent);
    var urlParsingNode = document.createElement('a');
    var originURL;

    /**
    * Parse a URL to discover it's components
    *
    * @param {String} url The URL to be parsed
    * @returns {Object}
    */
    function resolveURL(url) {
      var href = url;

      if (msie) {
        // IE needs attribute set twice to normalize properties
        urlParsingNode.setAttribute('href', href);
        href = urlParsingNode.href;
      }

      urlParsingNode.setAttribute('href', href);

      // urlParsingNode provides the UrlUtils interface - http://url.spec.whatwg.org/#urlutils
      return {
        href: urlParsingNode.href,
        protocol: urlParsingNode.protocol ? urlParsingNode.protocol.replace(/:$/, '') : '',
        host: urlParsingNode.host,
        search: urlParsingNode.search ? urlParsingNode.search.replace(/^\?/, '') : '',
        hash: urlParsingNode.hash ? urlParsingNode.hash.replace(/^#/, '') : '',
        hostname: urlParsingNode.hostname,
        port: urlParsingNode.port,
        pathname: (urlParsingNode.pathname.charAt(0) === '/') ?
                  urlParsingNode.pathname :
                  '/' + urlParsingNode.pathname
      };
    }

    originURL = resolveURL(window.location.href);

    /**
    * Determine if a URL shares the same origin as the current location
    *
    * @param {String} requestURL The URL to test
    * @returns {boolean} True if URL shares the same origin, otherwise false
    */
    return function isURLSameOrigin(requestURL) {
      var parsed = (utils.isString(requestURL)) ? resolveURL(requestURL) : requestURL;
      return (parsed.protocol === originURL.protocol &&
            parsed.host === originURL.host);
    };
  })() :

  // Non standard browser envs (web workers, react-native) lack needed support.
  (function nonStandardBrowserEnv() {
    return function isURLSameOrigin() {
      return true;
    };
  })()
);


/***/ }),

/***/ "./node_modules/axios/lib/helpers/normalizeHeaderName.js":
/*!***************************************************************!*\
  !*** ./node_modules/axios/lib/helpers/normalizeHeaderName.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "./node_modules/axios/lib/utils.js");

module.exports = function normalizeHeaderName(headers, normalizedName) {
  utils.forEach(headers, function processHeader(value, name) {
    if (name !== normalizedName && name.toUpperCase() === normalizedName.toUpperCase()) {
      headers[normalizedName] = value;
      delete headers[name];
    }
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/parseHeaders.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/parseHeaders.js ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

// Headers whose duplicates are ignored by node
// c.f. https://nodejs.org/api/http.html#http_message_headers
var ignoreDuplicateOf = [
  'age', 'authorization', 'content-length', 'content-type', 'etag',
  'expires', 'from', 'host', 'if-modified-since', 'if-unmodified-since',
  'last-modified', 'location', 'max-forwards', 'proxy-authorization',
  'referer', 'retry-after', 'user-agent'
];

/**
 * Parse headers into an object
 *
 * ```
 * Date: Wed, 27 Aug 2014 08:58:49 GMT
 * Content-Type: application/json
 * Connection: keep-alive
 * Transfer-Encoding: chunked
 * ```
 *
 * @param {String} headers Headers needing to be parsed
 * @returns {Object} Headers parsed into an object
 */
module.exports = function parseHeaders(headers) {
  var parsed = {};
  var key;
  var val;
  var i;

  if (!headers) { return parsed; }

  utils.forEach(headers.split('\n'), function parser(line) {
    i = line.indexOf(':');
    key = utils.trim(line.substr(0, i)).toLowerCase();
    val = utils.trim(line.substr(i + 1));

    if (key) {
      if (parsed[key] && ignoreDuplicateOf.indexOf(key) >= 0) {
        return;
      }
      if (key === 'set-cookie') {
        parsed[key] = (parsed[key] ? parsed[key] : []).concat([val]);
      } else {
        parsed[key] = parsed[key] ? parsed[key] + ', ' + val : val;
      }
    }
  });

  return parsed;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/spread.js":
/*!**************************************************!*\
  !*** ./node_modules/axios/lib/helpers/spread.js ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Syntactic sugar for invoking a function and expanding an array for arguments.
 *
 * Common use case would be to use `Function.prototype.apply`.
 *
 *  ```js
 *  function f(x, y, z) {}
 *  var args = [1, 2, 3];
 *  f.apply(null, args);
 *  ```
 *
 * With `spread` this example can be re-written.
 *
 *  ```js
 *  spread(function(x, y, z) {})([1, 2, 3]);
 *  ```
 *
 * @param {Function} callback
 * @returns {Function}
 */
module.exports = function spread(callback) {
  return function wrap(arr) {
    return callback.apply(null, arr);
  };
};


/***/ }),

/***/ "./node_modules/axios/lib/utils.js":
/*!*****************************************!*\
  !*** ./node_modules/axios/lib/utils.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var bind = __webpack_require__(/*! ./helpers/bind */ "./node_modules/axios/lib/helpers/bind.js");
var isBuffer = __webpack_require__(/*! is-buffer */ "./node_modules/axios/node_modules/is-buffer/index.js");

/*global toString:true*/

// utils is a library of generic helper functions non-specific to axios

var toString = Object.prototype.toString;

/**
 * Determine if a value is an Array
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an Array, otherwise false
 */
function isArray(val) {
  return toString.call(val) === '[object Array]';
}

/**
 * Determine if a value is an ArrayBuffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an ArrayBuffer, otherwise false
 */
function isArrayBuffer(val) {
  return toString.call(val) === '[object ArrayBuffer]';
}

/**
 * Determine if a value is a FormData
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an FormData, otherwise false
 */
function isFormData(val) {
  return (typeof FormData !== 'undefined') && (val instanceof FormData);
}

/**
 * Determine if a value is a view on an ArrayBuffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a view on an ArrayBuffer, otherwise false
 */
function isArrayBufferView(val) {
  var result;
  if ((typeof ArrayBuffer !== 'undefined') && (ArrayBuffer.isView)) {
    result = ArrayBuffer.isView(val);
  } else {
    result = (val) && (val.buffer) && (val.buffer instanceof ArrayBuffer);
  }
  return result;
}

/**
 * Determine if a value is a String
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a String, otherwise false
 */
function isString(val) {
  return typeof val === 'string';
}

/**
 * Determine if a value is a Number
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Number, otherwise false
 */
function isNumber(val) {
  return typeof val === 'number';
}

/**
 * Determine if a value is undefined
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if the value is undefined, otherwise false
 */
function isUndefined(val) {
  return typeof val === 'undefined';
}

/**
 * Determine if a value is an Object
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an Object, otherwise false
 */
function isObject(val) {
  return val !== null && typeof val === 'object';
}

/**
 * Determine if a value is a Date
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Date, otherwise false
 */
function isDate(val) {
  return toString.call(val) === '[object Date]';
}

/**
 * Determine if a value is a File
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a File, otherwise false
 */
function isFile(val) {
  return toString.call(val) === '[object File]';
}

/**
 * Determine if a value is a Blob
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Blob, otherwise false
 */
function isBlob(val) {
  return toString.call(val) === '[object Blob]';
}

/**
 * Determine if a value is a Function
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Function, otherwise false
 */
function isFunction(val) {
  return toString.call(val) === '[object Function]';
}

/**
 * Determine if a value is a Stream
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Stream, otherwise false
 */
function isStream(val) {
  return isObject(val) && isFunction(val.pipe);
}

/**
 * Determine if a value is a URLSearchParams object
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a URLSearchParams object, otherwise false
 */
function isURLSearchParams(val) {
  return typeof URLSearchParams !== 'undefined' && val instanceof URLSearchParams;
}

/**
 * Trim excess whitespace off the beginning and end of a string
 *
 * @param {String} str The String to trim
 * @returns {String} The String freed of excess whitespace
 */
function trim(str) {
  return str.replace(/^\s*/, '').replace(/\s*$/, '');
}

/**
 * Determine if we're running in a standard browser environment
 *
 * This allows axios to run in a web worker, and react-native.
 * Both environments support XMLHttpRequest, but not fully standard globals.
 *
 * web workers:
 *  typeof window -> undefined
 *  typeof document -> undefined
 *
 * react-native:
 *  navigator.product -> 'ReactNative'
 */
function isStandardBrowserEnv() {
  if (typeof navigator !== 'undefined' && navigator.product === 'ReactNative') {
    return false;
  }
  return (
    typeof window !== 'undefined' &&
    typeof document !== 'undefined'
  );
}

/**
 * Iterate over an Array or an Object invoking a function for each item.
 *
 * If `obj` is an Array callback will be called passing
 * the value, index, and complete array for each item.
 *
 * If 'obj' is an Object callback will be called passing
 * the value, key, and complete object for each property.
 *
 * @param {Object|Array} obj The object to iterate
 * @param {Function} fn The callback to invoke for each item
 */
function forEach(obj, fn) {
  // Don't bother if no value provided
  if (obj === null || typeof obj === 'undefined') {
    return;
  }

  // Force an array if not already something iterable
  if (typeof obj !== 'object') {
    /*eslint no-param-reassign:0*/
    obj = [obj];
  }

  if (isArray(obj)) {
    // Iterate over array values
    for (var i = 0, l = obj.length; i < l; i++) {
      fn.call(null, obj[i], i, obj);
    }
  } else {
    // Iterate over object keys
    for (var key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        fn.call(null, obj[key], key, obj);
      }
    }
  }
}

/**
 * Accepts varargs expecting each argument to be an object, then
 * immutably merges the properties of each object and returns result.
 *
 * When multiple objects contain the same key the later object in
 * the arguments list will take precedence.
 *
 * Example:
 *
 * ```js
 * var result = merge({foo: 123}, {foo: 456});
 * console.log(result.foo); // outputs 456
 * ```
 *
 * @param {Object} obj1 Object to merge
 * @returns {Object} Result of all merge properties
 */
function merge(/* obj1, obj2, obj3, ... */) {
  var result = {};
  function assignValue(val, key) {
    if (typeof result[key] === 'object' && typeof val === 'object') {
      result[key] = merge(result[key], val);
    } else {
      result[key] = val;
    }
  }

  for (var i = 0, l = arguments.length; i < l; i++) {
    forEach(arguments[i], assignValue);
  }
  return result;
}

/**
 * Extends object a by mutably adding to it the properties of object b.
 *
 * @param {Object} a The object to be extended
 * @param {Object} b The object to copy properties from
 * @param {Object} thisArg The object to bind function to
 * @return {Object} The resulting value of object a
 */
function extend(a, b, thisArg) {
  forEach(b, function assignValue(val, key) {
    if (thisArg && typeof val === 'function') {
      a[key] = bind(val, thisArg);
    } else {
      a[key] = val;
    }
  });
  return a;
}

module.exports = {
  isArray: isArray,
  isArrayBuffer: isArrayBuffer,
  isBuffer: isBuffer,
  isFormData: isFormData,
  isArrayBufferView: isArrayBufferView,
  isString: isString,
  isNumber: isNumber,
  isObject: isObject,
  isUndefined: isUndefined,
  isDate: isDate,
  isFile: isFile,
  isBlob: isBlob,
  isFunction: isFunction,
  isStream: isStream,
  isURLSearchParams: isURLSearchParams,
  isStandardBrowserEnv: isStandardBrowserEnv,
  forEach: forEach,
  merge: merge,
  extend: extend,
  trim: trim
};


/***/ }),

/***/ "./node_modules/axios/node_modules/is-buffer/index.js":
/*!************************************************************!*\
  !*** ./node_modules/axios/node_modules/is-buffer/index.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*!
 * Determine if an object is a Buffer
 *
 * @author   Feross Aboukhadijeh <https://feross.org>
 * @license  MIT
 */

module.exports = function isBuffer (obj) {
  return obj != null && obj.constructor != null &&
    typeof obj.constructor.isBuffer === 'function' && obj.constructor.isBuffer(obj)
}


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--4-0!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  /*
   * The component's data.
   */
  data: function data() {
    return {
      tokens: []
    };
  },

  /**
   * Prepare the component (Vue 1.x).
   */
  ready: function ready() {
    this.prepareComponent();
  },

  /**
   * Prepare the component (Vue 2.x).
   */
  mounted: function mounted() {
    this.prepareComponent();
  },
  methods: {
    /**
     * Prepare the component (Vue 2.x).
     */
    prepareComponent: function prepareComponent() {
      this.getTokens();
    },

    /**
     * Get all of the authorized tokens for the user.
     */
    getTokens: function getTokens() {
      var _this = this;

      axios.get('./oauth/tokens').then(function (response) {
        _this.tokens = response.data;
      });
    },

    /**
     * Revoke the given token.
     */
    revoke: function revoke(token) {
      var _this2 = this;

      axios["delete"]('./oauth/tokens/' + token.id).then(function (response) {
        _this2.getTokens();
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/Clients.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--4-0!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/Clients.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  /*
   * The component's data.
   */
  data: function data() {
    return {
      clients: [],
      createForm: {
        errors: [],
        name: '',
        redirect: ''
      },
      editForm: {
        errors: [],
        name: '',
        redirect: ''
      }
    };
  },

  /**
   * Prepare the component (Vue 1.x).
   */
  ready: function ready() {
    this.prepareComponent();
  },

  /**
   * Prepare the component (Vue 2.x).
   */
  mounted: function mounted() {
    this.prepareComponent();
  },
  methods: {
    /**
     * Prepare the component.
     */
    prepareComponent: function prepareComponent() {
      this.getClients();
      $('#modal-create-client').on('shown.bs.modal', function () {
        $('#create-client-name').focus();
      });
      $('#modal-edit-client').on('shown.bs.modal', function () {
        $('#edit-client-name').focus();
      });
    },

    /**
     * Get all of the OAuth clients for the user.
     */
    getClients: function getClients() {
      var _this = this;

      axios.get('./oauth/clients').then(function (response) {
        _this.clients = response.data;
      });
    },

    /**
     * Show the form for creating new clients.
     */
    showCreateClientForm: function showCreateClientForm() {
      $('#modal-create-client').modal('show');
    },

    /**
     * Create a new OAuth client for the user.
     */
    store: function store() {
      this.persistClient('post', './oauth/clients' + '?_token=' + document.head.querySelector('meta[name="csrf-token"]').content, this.createForm, '#modal-create-client');
    },

    /**
     * Edit the given client.
     */
    edit: function edit(client) {
      this.editForm.id = client.id;
      this.editForm.name = client.name;
      this.editForm.redirect = client.redirect;
      $('#modal-edit-client').modal('show');
    },

    /**
     * Update the client being edited.
     */
    update: function update() {
      this.persistClient('put', './oauth/clients/' + this.editForm.id + '?_token=' + document.head.querySelector('meta[name="csrf-token"]').content, this.editForm, '#modal-edit-client');
    },

    /**
     * Persist the client to storage using the given form.
     */
    persistClient: function persistClient(method, uri, form, modal) {
      var _this2 = this;

      form.errors = [];
      axios[method](uri, form).then(function (response) {
        _this2.getClients();

        form.name = '';
        form.redirect = '';
        form.errors = [];
        $(modal).modal('hide');
      })["catch"](function (error) {
        if (_typeof(error.response.data) === 'object') {
          form.errors = _.flatten(_.toArray(error.response.data));
        } else {
          form.errors = [$t('firefly.try_again')];
        }
      });
    },

    /**
     * Destroy the given client.
     */
    destroy: function destroy(client) {
      var _this3 = this;

      axios["delete"]('./oauth/clients/' + client.id).then(function (response) {
        _this3.getClients();
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--4-0!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  /*
   * The component's data.
   */
  data: function data() {
    return {
      accessToken: null,
      tokens: [],
      scopes: [],
      form: {
        name: '',
        scopes: [],
        errors: []
      }
    };
  },

  /**
   * Prepare the component (Vue 1.x).
   */
  ready: function ready() {
    this.prepareComponent();
  },

  /**
   * Prepare the component (Vue 2.x).
   */
  mounted: function mounted() {
    this.prepareComponent();
  },
  methods: {
    /**
     * Prepare the component.
     */
    prepareComponent: function prepareComponent() {
      this.getTokens();
      this.getScopes();
      $('#modal-create-token').on('shown.bs.modal', function () {
        $('#create-token-name').focus();
      });
    },

    /**
     * Get all of the personal access tokens for the user.
     */
    getTokens: function getTokens() {
      var _this = this;

      axios.get('./oauth/personal-access-tokens').then(function (response) {
        _this.tokens = response.data;
      });
    },

    /**
     * Get all of the available scopes.
     */
    getScopes: function getScopes() {
      var _this2 = this;

      axios.get('./oauth/scopes').then(function (response) {
        _this2.scopes = response.data;
      });
    },

    /**
     * Show the form for creating new tokens.
     */
    showCreateTokenForm: function showCreateTokenForm() {
      $('#modal-create-token').modal('show');
    },

    /**
     * Create a new personal access token.
     */
    store: function store() {
      var _this3 = this;

      this.accessToken = null;
      this.form.errors = [];
      axios.post('./oauth/personal-access-tokens?_token=' + document.head.querySelector('meta[name="csrf-token"]').content, this.form).then(function (response) {
        _this3.form.name = '';
        _this3.form.scopes = [];
        _this3.form.errors = [];

        _this3.tokens.push(response.data.token);

        _this3.showAccessToken(response.data.accessToken);
      })["catch"](function (error) {
        if (_typeof(error.response.data) === 'object') {
          _this3.form.errors = _.flatten(_.toArray(error.response.data));
        } else {
          _this3.form.errors = [$t('firefly.try_again')];
        }
      });
    },

    /**
     * Toggle the given scope in the list of assigned scopes.
     */
    toggleScope: function toggleScope(scope) {
      if (this.scopeIsAssigned(scope)) {
        this.form.scopes = _.reject(this.form.scopes, function (s) {
          return s == scope;
        });
      } else {
        this.form.scopes.push(scope);
      }
    },

    /**
     * Determine if the given scope has been assigned to the token.
     */
    scopeIsAssigned: function scopeIsAssigned(scope) {
      return _.indexOf(this.form.scopes, scope) >= 0;
    },

    /**
     * Show the given access token to the user.
     */
    showAccessToken: function showAccessToken(accessToken) {
      $('#modal-create-token').modal('hide');
      this.accessToken = accessToken;
      $('#modal-access-token').modal('show');
    },

    /**
     * Revoke the given token.
     */
    revoke: function revoke(token) {
      var _this4 = this;

      axios["delete"]('./oauth/personal-access-tokens/' + token.id).then(function (response) {
        _this4.getTokens();
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--4-0!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  name: "ProfileOptions"
});

/***/ }),

/***/ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader??ref--5-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--5-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../../node_modules/css-loader/lib/css-base.js */ "./node_modules/css-loader/lib/css-base.js")(false);
// imports


// module
exports.push([module.i, "\n.action-link[data-v-2ee9fe67] {\n    cursor: pointer;\n}\n.m-b-none[data-v-2ee9fe67] {\n    margin-bottom: 0;\n}\n", ""]);

// exports


/***/ }),

/***/ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader??ref--5-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--5-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../../node_modules/css-loader/lib/css-base.js */ "./node_modules/css-loader/lib/css-base.js")(false);
// imports


// module
exports.push([module.i, "\n.action-link[data-v-5d1d7d82] {\n    cursor: pointer;\n}\n.m-b-none[data-v-5d1d7d82] {\n    margin-bottom: 0;\n}\n", ""]);

// exports


/***/ }),

/***/ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader??ref--5-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--5-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../../node_modules/css-loader/lib/css-base.js */ "./node_modules/css-loader/lib/css-base.js")(false);
// imports


// module
exports.push([module.i, "\n.action-link[data-v-89c53f18] {\n    cursor: pointer;\n}\n.m-b-none[data-v-89c53f18] {\n    margin-bottom: 0;\n}\n", ""]);

// exports


/***/ }),

/***/ "./node_modules/css-loader/lib/css-base.js":
/*!*************************************************!*\
  !*** ./node_modules/css-loader/lib/css-base.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
module.exports = function(useSourceMap) {
	var list = [];

	// return the list of modules as css string
	list.toString = function toString() {
		return this.map(function (item) {
			var content = cssWithMappingToString(item, useSourceMap);
			if(item[2]) {
				return "@media " + item[2] + "{" + content + "}";
			} else {
				return content;
			}
		}).join("");
	};

	// import a list of modules into the list
	list.i = function(modules, mediaQuery) {
		if(typeof modules === "string")
			modules = [[null, modules, ""]];
		var alreadyImportedModules = {};
		for(var i = 0; i < this.length; i++) {
			var id = this[i][0];
			if(typeof id === "number")
				alreadyImportedModules[id] = true;
		}
		for(i = 0; i < modules.length; i++) {
			var item = modules[i];
			// skip already imported module
			// this implementation is not 100% perfect for weird media query combinations
			//  when a module is imported multiple times with different media queries.
			//  I hope this will never occur (Hey this way we have smaller bundles)
			if(typeof item[0] !== "number" || !alreadyImportedModules[item[0]]) {
				if(mediaQuery && !item[2]) {
					item[2] = mediaQuery;
				} else if(mediaQuery) {
					item[2] = "(" + item[2] + ") and (" + mediaQuery + ")";
				}
				list.push(item);
			}
		}
	};
	return list;
};

function cssWithMappingToString(item, useSourceMap) {
	var content = item[1] || '';
	var cssMapping = item[3];
	if (!cssMapping) {
		return content;
	}

	if (useSourceMap && typeof btoa === 'function') {
		var sourceMapping = toComment(cssMapping);
		var sourceURLs = cssMapping.sources.map(function (source) {
			return '/*# sourceURL=' + cssMapping.sourceRoot + source + ' */'
		});

		return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
	}

	return [content].join('\n');
}

// Adapted from convert-source-map (MIT)
function toComment(sourceMap) {
	// eslint-disable-next-line no-undef
	var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap))));
	var data = 'sourceMappingURL=data:application/json;charset=utf-8;base64,' + base64;

	return '/*# ' + data + ' */';
}


/***/ }),

/***/ "./node_modules/process/browser.js":
/*!*****************************************!*\
  !*** ./node_modules/process/browser.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };


/***/ }),

/***/ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader!./node_modules/css-loader??ref--5-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--5-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../../../node_modules/css-loader??ref--5-1!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/postcss-loader/src??ref--5-2!../../../../../node_modules/vue-loader/lib??vue-loader-options!./AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css& */ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css&");

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(/*! ../../../../../node_modules/style-loader/lib/addStyles.js */ "./node_modules/style-loader/lib/addStyles.js")(content, options);

if(content.locals) module.exports = content.locals;

if(false) {}

/***/ }),

/***/ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader!./node_modules/css-loader??ref--5-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--5-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../../../node_modules/css-loader??ref--5-1!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/postcss-loader/src??ref--5-2!../../../../../node_modules/vue-loader/lib??vue-loader-options!./Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css& */ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css&");

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(/*! ../../../../../node_modules/style-loader/lib/addStyles.js */ "./node_modules/style-loader/lib/addStyles.js")(content, options);

if(content.locals) module.exports = content.locals;

if(false) {}

/***/ }),

/***/ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader!./node_modules/css-loader??ref--5-1!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src??ref--5-2!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../../../node_modules/css-loader??ref--5-1!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/postcss-loader/src??ref--5-2!../../../../../node_modules/vue-loader/lib??vue-loader-options!./PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css& */ "./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css&");

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(/*! ../../../../../node_modules/style-loader/lib/addStyles.js */ "./node_modules/style-loader/lib/addStyles.js")(content, options);

if(content.locals) module.exports = content.locals;

if(false) {}

/***/ }),

/***/ "./node_modules/style-loader/lib/addStyles.js":
/*!****************************************************!*\
  !*** ./node_modules/style-loader/lib/addStyles.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/

var stylesInDom = {};

var	memoize = function (fn) {
	var memo;

	return function () {
		if (typeof memo === "undefined") memo = fn.apply(this, arguments);
		return memo;
	};
};

var isOldIE = memoize(function () {
	// Test for IE <= 9 as proposed by Browserhacks
	// @see http://browserhacks.com/#hack-e71d8692f65334173fee715c222cb805
	// Tests for existence of standard globals is to allow style-loader
	// to operate correctly into non-standard environments
	// @see https://github.com/webpack-contrib/style-loader/issues/177
	return window && document && document.all && !window.atob;
});

var getTarget = function (target, parent) {
  if (parent){
    return parent.querySelector(target);
  }
  return document.querySelector(target);
};

var getElement = (function (fn) {
	var memo = {};

	return function(target, parent) {
                // If passing function in options, then use it for resolve "head" element.
                // Useful for Shadow Root style i.e
                // {
                //   insertInto: function () { return document.querySelector("#foo").shadowRoot }
                // }
                if (typeof target === 'function') {
                        return target();
                }
                if (typeof memo[target] === "undefined") {
			var styleTarget = getTarget.call(this, target, parent);
			// Special case to return head of iframe instead of iframe itself
			if (window.HTMLIFrameElement && styleTarget instanceof window.HTMLIFrameElement) {
				try {
					// This will throw an exception if access to iframe is blocked
					// due to cross-origin restrictions
					styleTarget = styleTarget.contentDocument.head;
				} catch(e) {
					styleTarget = null;
				}
			}
			memo[target] = styleTarget;
		}
		return memo[target]
	};
})();

var singleton = null;
var	singletonCounter = 0;
var	stylesInsertedAtTop = [];

var	fixUrls = __webpack_require__(/*! ./urls */ "./node_modules/style-loader/lib/urls.js");

module.exports = function(list, options) {
	if (typeof DEBUG !== "undefined" && DEBUG) {
		if (typeof document !== "object") throw new Error("The style-loader cannot be used in a non-browser environment");
	}

	options = options || {};

	options.attrs = typeof options.attrs === "object" ? options.attrs : {};

	// Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
	// tags it will allow on a page
	if (!options.singleton && typeof options.singleton !== "boolean") options.singleton = isOldIE();

	// By default, add <style> tags to the <head> element
        if (!options.insertInto) options.insertInto = "head";

	// By default, add <style> tags to the bottom of the target
	if (!options.insertAt) options.insertAt = "bottom";

	var styles = listToStyles(list, options);

	addStylesToDom(styles, options);

	return function update (newList) {
		var mayRemove = [];

		for (var i = 0; i < styles.length; i++) {
			var item = styles[i];
			var domStyle = stylesInDom[item.id];

			domStyle.refs--;
			mayRemove.push(domStyle);
		}

		if(newList) {
			var newStyles = listToStyles(newList, options);
			addStylesToDom(newStyles, options);
		}

		for (var i = 0; i < mayRemove.length; i++) {
			var domStyle = mayRemove[i];

			if(domStyle.refs === 0) {
				for (var j = 0; j < domStyle.parts.length; j++) domStyle.parts[j]();

				delete stylesInDom[domStyle.id];
			}
		}
	};
};

function addStylesToDom (styles, options) {
	for (var i = 0; i < styles.length; i++) {
		var item = styles[i];
		var domStyle = stylesInDom[item.id];

		if(domStyle) {
			domStyle.refs++;

			for(var j = 0; j < domStyle.parts.length; j++) {
				domStyle.parts[j](item.parts[j]);
			}

			for(; j < item.parts.length; j++) {
				domStyle.parts.push(addStyle(item.parts[j], options));
			}
		} else {
			var parts = [];

			for(var j = 0; j < item.parts.length; j++) {
				parts.push(addStyle(item.parts[j], options));
			}

			stylesInDom[item.id] = {id: item.id, refs: 1, parts: parts};
		}
	}
}

function listToStyles (list, options) {
	var styles = [];
	var newStyles = {};

	for (var i = 0; i < list.length; i++) {
		var item = list[i];
		var id = options.base ? item[0] + options.base : item[0];
		var css = item[1];
		var media = item[2];
		var sourceMap = item[3];
		var part = {css: css, media: media, sourceMap: sourceMap};

		if(!newStyles[id]) styles.push(newStyles[id] = {id: id, parts: [part]});
		else newStyles[id].parts.push(part);
	}

	return styles;
}

function insertStyleElement (options, style) {
	var target = getElement(options.insertInto)

	if (!target) {
		throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");
	}

	var lastStyleElementInsertedAtTop = stylesInsertedAtTop[stylesInsertedAtTop.length - 1];

	if (options.insertAt === "top") {
		if (!lastStyleElementInsertedAtTop) {
			target.insertBefore(style, target.firstChild);
		} else if (lastStyleElementInsertedAtTop.nextSibling) {
			target.insertBefore(style, lastStyleElementInsertedAtTop.nextSibling);
		} else {
			target.appendChild(style);
		}
		stylesInsertedAtTop.push(style);
	} else if (options.insertAt === "bottom") {
		target.appendChild(style);
	} else if (typeof options.insertAt === "object" && options.insertAt.before) {
		var nextSibling = getElement(options.insertAt.before, target);
		target.insertBefore(style, nextSibling);
	} else {
		throw new Error("[Style Loader]\n\n Invalid value for parameter 'insertAt' ('options.insertAt') found.\n Must be 'top', 'bottom', or Object.\n (https://github.com/webpack-contrib/style-loader#insertat)\n");
	}
}

function removeStyleElement (style) {
	if (style.parentNode === null) return false;
	style.parentNode.removeChild(style);

	var idx = stylesInsertedAtTop.indexOf(style);
	if(idx >= 0) {
		stylesInsertedAtTop.splice(idx, 1);
	}
}

function createStyleElement (options) {
	var style = document.createElement("style");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}

	if(options.attrs.nonce === undefined) {
		var nonce = getNonce();
		if (nonce) {
			options.attrs.nonce = nonce;
		}
	}

	addAttrs(style, options.attrs);
	insertStyleElement(options, style);

	return style;
}

function createLinkElement (options) {
	var link = document.createElement("link");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}
	options.attrs.rel = "stylesheet";

	addAttrs(link, options.attrs);
	insertStyleElement(options, link);

	return link;
}

function addAttrs (el, attrs) {
	Object.keys(attrs).forEach(function (key) {
		el.setAttribute(key, attrs[key]);
	});
}

function getNonce() {
	if (false) {}

	return __webpack_require__.nc;
}

function addStyle (obj, options) {
	var style, update, remove, result;

	// If a transform function was defined, run it on the css
	if (options.transform && obj.css) {
	    result = typeof options.transform === 'function'
		 ? options.transform(obj.css) 
		 : options.transform.default(obj.css);

	    if (result) {
	    	// If transform returns a value, use that instead of the original css.
	    	// This allows running runtime transformations on the css.
	    	obj.css = result;
	    } else {
	    	// If the transform function returns a falsy value, don't add this css.
	    	// This allows conditional loading of css
	    	return function() {
	    		// noop
	    	};
	    }
	}

	if (options.singleton) {
		var styleIndex = singletonCounter++;

		style = singleton || (singleton = createStyleElement(options));

		update = applyToSingletonTag.bind(null, style, styleIndex, false);
		remove = applyToSingletonTag.bind(null, style, styleIndex, true);

	} else if (
		obj.sourceMap &&
		typeof URL === "function" &&
		typeof URL.createObjectURL === "function" &&
		typeof URL.revokeObjectURL === "function" &&
		typeof Blob === "function" &&
		typeof btoa === "function"
	) {
		style = createLinkElement(options);
		update = updateLink.bind(null, style, options);
		remove = function () {
			removeStyleElement(style);

			if(style.href) URL.revokeObjectURL(style.href);
		};
	} else {
		style = createStyleElement(options);
		update = applyToTag.bind(null, style);
		remove = function () {
			removeStyleElement(style);
		};
	}

	update(obj);

	return function updateStyle (newObj) {
		if (newObj) {
			if (
				newObj.css === obj.css &&
				newObj.media === obj.media &&
				newObj.sourceMap === obj.sourceMap
			) {
				return;
			}

			update(obj = newObj);
		} else {
			remove();
		}
	};
}

var replaceText = (function () {
	var textStore = [];

	return function (index, replacement) {
		textStore[index] = replacement;

		return textStore.filter(Boolean).join('\n');
	};
})();

function applyToSingletonTag (style, index, remove, obj) {
	var css = remove ? "" : obj.css;

	if (style.styleSheet) {
		style.styleSheet.cssText = replaceText(index, css);
	} else {
		var cssNode = document.createTextNode(css);
		var childNodes = style.childNodes;

		if (childNodes[index]) style.removeChild(childNodes[index]);

		if (childNodes.length) {
			style.insertBefore(cssNode, childNodes[index]);
		} else {
			style.appendChild(cssNode);
		}
	}
}

function applyToTag (style, obj) {
	var css = obj.css;
	var media = obj.media;

	if(media) {
		style.setAttribute("media", media)
	}

	if(style.styleSheet) {
		style.styleSheet.cssText = css;
	} else {
		while(style.firstChild) {
			style.removeChild(style.firstChild);
		}

		style.appendChild(document.createTextNode(css));
	}
}

function updateLink (link, options, obj) {
	var css = obj.css;
	var sourceMap = obj.sourceMap;

	/*
		If convertToAbsoluteUrls isn't defined, but sourcemaps are enabled
		and there is no publicPath defined then lets turn convertToAbsoluteUrls
		on by default.  Otherwise default to the convertToAbsoluteUrls option
		directly
	*/
	var autoFixUrls = options.convertToAbsoluteUrls === undefined && sourceMap;

	if (options.convertToAbsoluteUrls || autoFixUrls) {
		css = fixUrls(css);
	}

	if (sourceMap) {
		// http://stackoverflow.com/a/26603875
		css += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))) + " */";
	}

	var blob = new Blob([css], { type: "text/css" });

	var oldSrc = link.href;

	link.href = URL.createObjectURL(blob);

	if(oldSrc) URL.revokeObjectURL(oldSrc);
}


/***/ }),

/***/ "./node_modules/style-loader/lib/urls.js":
/*!***********************************************!*\
  !*** ./node_modules/style-loader/lib/urls.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {


/**
 * When source maps are enabled, `style-loader` uses a link element with a data-uri to
 * embed the css on the page. This breaks all relative urls because now they are relative to a
 * bundle instead of the current page.
 *
 * One solution is to only use full urls, but that may be impossible.
 *
 * Instead, this function "fixes" the relative urls to be absolute according to the current page location.
 *
 * A rudimentary test suite is located at `test/fixUrls.js` and can be run via the `npm test` command.
 *
 */

module.exports = function (css) {
  // get current location
  var location = typeof window !== "undefined" && window.location;

  if (!location) {
    throw new Error("fixUrls requires window.location");
  }

	// blank or null?
	if (!css || typeof css !== "string") {
	  return css;
  }

  var baseUrl = location.protocol + "//" + location.host;
  var currentDir = baseUrl + location.pathname.replace(/\/[^\/]*$/, "/");

	// convert each url(...)
	/*
	This regular expression is just a way to recursively match brackets within
	a string.

	 /url\s*\(  = Match on the word "url" with any whitespace after it and then a parens
	   (  = Start a capturing group
	     (?:  = Start a non-capturing group
	         [^)(]  = Match anything that isn't a parentheses
	         |  = OR
	         \(  = Match a start parentheses
	             (?:  = Start another non-capturing groups
	                 [^)(]+  = Match anything that isn't a parentheses
	                 |  = OR
	                 \(  = Match a start parentheses
	                     [^)(]*  = Match anything that isn't a parentheses
	                 \)  = Match a end parentheses
	             )  = End Group
              *\) = Match anything and then a close parens
          )  = Close non-capturing group
          *  = Match anything
       )  = Close capturing group
	 \)  = Match a close parens

	 /gi  = Get all matches, not the first.  Be case insensitive.
	 */
	var fixedCss = css.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi, function(fullMatch, origUrl) {
		// strip quotes (if they exist)
		var unquotedOrigUrl = origUrl
			.trim()
			.replace(/^"(.*)"$/, function(o, $1){ return $1; })
			.replace(/^'(.*)'$/, function(o, $1){ return $1; });

		// already a full url? no change
		if (/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/|\s*$)/i.test(unquotedOrigUrl)) {
		  return fullMatch;
		}

		// convert the url to a full url
		var newUrl;

		if (unquotedOrigUrl.indexOf("//") === 0) {
		  	//TODO: should we add protocol?
			newUrl = unquotedOrigUrl;
		} else if (unquotedOrigUrl.indexOf("/") === 0) {
			// path should be relative to the base url
			newUrl = baseUrl + unquotedOrigUrl; // already starts with '/'
		} else {
			// path should be relative to current directory
			newUrl = currentDir + unquotedOrigUrl.replace(/^\.\//, ""); // Strip leading './'
		}

		// send back the fixed url(...)
		return "url(" + JSON.stringify(newUrl) + ")";
	});

	// send back the fixed css
	return fixedCss;
};


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=template&id=2ee9fe67&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=template&id=2ee9fe67&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", [
    _vm.tokens.length > 0
      ? _c("div", [
          _c("div", { staticClass: "box box-primary" }, [
            _c("div", { staticClass: "box-header with-border" }, [
              _c("h3", { staticClass: "box-title" }, [
                _vm._v(
                  "\n                    " +
                    _vm._s(_vm.$t("profile.authorized_apps")) +
                    "\n                "
                )
              ])
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "box-body" }, [
              _c("table", { staticClass: "table table-borderless m-b-none" }, [
                _c("caption", [
                  _vm._v(_vm._s(_vm.$t("profile.authorized_clients")))
                ]),
                _vm._v(" "),
                _c("thead", [
                  _c("tr", [
                    _c("th", { attrs: { scope: "col" } }, [
                      _vm._v(_vm._s(_vm.$t("firefly.name")))
                    ]),
                    _vm._v(" "),
                    _c("th", { attrs: { scope: "col" } }, [
                      _vm._v(_vm._s(_vm.$t("profile.scopes")))
                    ]),
                    _vm._v(" "),
                    _c("th", { attrs: { scope: "col" } })
                  ])
                ]),
                _vm._v(" "),
                _c(
                  "tbody",
                  _vm._l(_vm.tokens, function(token) {
                    return _c("tr", [
                      _c(
                        "td",
                        { staticStyle: { "vertical-align": "middle" } },
                        [
                          _vm._v(
                            "\n                            " +
                              _vm._s(token.client.name) +
                              "\n                        "
                          )
                        ]
                      ),
                      _vm._v(" "),
                      _c(
                        "td",
                        { staticStyle: { "vertical-align": "middle" } },
                        [
                          token.scopes.length > 0
                            ? _c("span", [
                                _vm._v(
                                  "\n                                    " +
                                    _vm._s(token.scopes.join(", ")) +
                                    "\n                                "
                                )
                              ])
                            : _vm._e()
                        ]
                      ),
                      _vm._v(" "),
                      _c(
                        "td",
                        { staticStyle: { "vertical-align": "middle" } },
                        [
                          _c(
                            "a",
                            {
                              staticClass: "action-link btn btn-danger btn-xs",
                              on: {
                                click: function($event) {
                                  return _vm.revoke(token)
                                }
                              }
                            },
                            [
                              _vm._v(
                                "\n                                " +
                                  _vm._s(_vm.$t("profile.revoke")) +
                                  "\n                            "
                              )
                            ]
                          )
                        ]
                      )
                    ])
                  }),
                  0
                )
              ])
            ])
          ])
        ])
      : _vm._e()
  ])
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/Clients.vue?vue&type=template&id=5d1d7d82&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/Clients.vue?vue&type=template&id=5d1d7d82&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", [
    _c("div", { staticClass: "box box-primary" }, [
      _c("div", { staticClass: "box-header with-border" }, [
        _c("h3", { staticClass: "box-title" }, [
          _vm._v(
            "\n                    " +
              _vm._s(_vm.$t("profile.oauth_clients")) +
              "\n            "
          )
        ])
      ]),
      _vm._v(" "),
      _c("div", { staticClass: "box-body" }, [
        _vm.clients.length === 0
          ? _c("p", { staticClass: "m-b-none" }, [
              _vm._v(
                "\n                " +
                  _vm._s(_vm.$t("profile.oauth_no_clients")) +
                  "\n            "
              )
            ])
          : _vm._e(),
        _vm._v(" "),
        _vm.clients.length > 0
          ? _c("table", { staticClass: "table table-borderless m-b-none" }, [
              _c("caption", [
                _vm._v(_vm._s(_vm.$t("profile.oauth_clients_header")))
              ]),
              _vm._v(" "),
              _c("thead", [
                _c("tr", [
                  _c("th", { attrs: { scope: "col" } }, [
                    _vm._v(_vm._s(_vm.$t("profile.oauth_client_id")))
                  ]),
                  _vm._v(" "),
                  _c("th", { attrs: { scope: "col" } }, [
                    _vm._v(_vm._s(_vm.$t("firefly.name")))
                  ]),
                  _vm._v(" "),
                  _c("th", { attrs: { scope: "col" } }, [
                    _vm._v(_vm._s(_vm.$t("profile.oauth_client_secret")))
                  ]),
                  _vm._v(" "),
                  _c("th", { attrs: { scope: "col" } }),
                  _vm._v(" "),
                  _c("th", { attrs: { scope: "col" } })
                ])
              ]),
              _vm._v(" "),
              _c(
                "tbody",
                _vm._l(_vm.clients, function(client) {
                  return _c("tr", [
                    _c("td", { staticStyle: { "vertical-align": "middle" } }, [
                      _vm._v(
                        "\n                            " +
                          _vm._s(client.id) +
                          "\n                        "
                      )
                    ]),
                    _vm._v(" "),
                    _c("td", { staticStyle: { "vertical-align": "middle" } }, [
                      _vm._v(
                        "\n                            " +
                          _vm._s(client.name) +
                          "\n                        "
                      )
                    ]),
                    _vm._v(" "),
                    _c("td", { staticStyle: { "vertical-align": "middle" } }, [
                      _c("code", [_vm._v(_vm._s(client.secret))])
                    ]),
                    _vm._v(" "),
                    _c("td", { staticStyle: { "vertical-align": "middle" } }, [
                      _c(
                        "a",
                        {
                          staticClass: "action-link btn btn-default btn-xs",
                          on: {
                            click: function($event) {
                              return _vm.edit(client)
                            }
                          }
                        },
                        [
                          _vm._v(
                            "\n                                " +
                              _vm._s(_vm.$t("firefly.edit")) +
                              "\n                            "
                          )
                        ]
                      )
                    ]),
                    _vm._v(" "),
                    _c("td", { staticStyle: { "vertical-align": "middle" } }, [
                      _c(
                        "a",
                        {
                          staticClass: "action-link btn btn-danger btn-xs",
                          on: {
                            click: function($event) {
                              return _vm.destroy(client)
                            }
                          }
                        },
                        [
                          _vm._v(
                            "\n                                " +
                              _vm._s(_vm.$t("firefly.delete")) +
                              "\n                            "
                          )
                        ]
                      )
                    ])
                  ])
                }),
                0
              )
            ])
          : _vm._e()
      ]),
      _vm._v(" "),
      _c("div", { staticClass: "box-footer" }, [
        _c(
          "a",
          {
            staticClass: "action-link btn btn-success",
            on: { click: _vm.showCreateClientForm }
          },
          [
            _vm._v(
              "\n                " +
                _vm._s(_vm.$t("profile.oauth_create_new_client")) +
                "\n            "
            )
          ]
        )
      ])
    ]),
    _vm._v(" "),
    _c(
      "div",
      {
        staticClass: "modal fade",
        attrs: { id: "modal-create-client", tabindex: "-1", role: "dialog" }
      },
      [
        _c("div", { staticClass: "modal-dialog" }, [
          _c("div", { staticClass: "modal-content" }, [
            _c("div", { staticClass: "modal-header" }, [
              _c(
                "button",
                {
                  staticClass: "close",
                  attrs: {
                    type: "button",
                    "data-dismiss": "modal",
                    "aria-hidden": "true"
                  }
                },
                [_vm._v("×")]
              ),
              _vm._v(" "),
              _c("h4", { staticClass: "modal-title" }, [
                _vm._v(
                  "\n                        " +
                    _vm._s(_vm.$t("profile.oauth_create_client")) +
                    "\n                    "
                )
              ])
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "modal-body" }, [
              _vm.createForm.errors.length > 0
                ? _c("div", { staticClass: "alert alert-danger" }, [
                    _c("p", [
                      _c("strong", [_vm._v(_vm._s(_vm.$t("firefly.whoops")))]),
                      _vm._v(" " + _vm._s(_vm.$t("firefly.something_wrong")))
                    ]),
                    _vm._v(" "),
                    _c("br"),
                    _vm._v(" "),
                    _c(
                      "ul",
                      _vm._l(_vm.createForm.errors, function(error) {
                        return _c("li", [
                          _vm._v(
                            "\n                                " +
                              _vm._s(error) +
                              "\n                            "
                          )
                        ])
                      }),
                      0
                    )
                  ])
                : _vm._e(),
              _vm._v(" "),
              _c(
                "form",
                { staticClass: "form-horizontal", attrs: { role: "form" } },
                [
                  _c("div", { staticClass: "form-group" }, [
                    _c("label", { staticClass: "col-md-3 control-label" }, [
                      _vm._v(_vm._s(_vm.$t("firefly.name")))
                    ]),
                    _vm._v(" "),
                    _c("div", { staticClass: "col-md-7" }, [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.createForm.name,
                            expression: "createForm.name"
                          }
                        ],
                        staticClass: "form-control",
                        attrs: { id: "create-client-name", type: "text" },
                        domProps: { value: _vm.createForm.name },
                        on: {
                          keyup: function($event) {
                            if (
                              !$event.type.indexOf("key") &&
                              _vm._k(
                                $event.keyCode,
                                "enter",
                                13,
                                $event.key,
                                "Enter"
                              )
                            ) {
                              return null
                            }
                            return _vm.store($event)
                          },
                          input: function($event) {
                            if ($event.target.composing) {
                              return
                            }
                            _vm.$set(
                              _vm.createForm,
                              "name",
                              $event.target.value
                            )
                          }
                        }
                      }),
                      _vm._v(" "),
                      _c("span", { staticClass: "help-block" }, [
                        _vm._v(
                          "\n                                    " +
                            _vm._s(_vm.$t("profile.oauth_name_help")) +
                            "\n                                "
                        )
                      ])
                    ])
                  ]),
                  _vm._v(" "),
                  _c("div", { staticClass: "form-group" }, [
                    _c("label", { staticClass: "col-md-3 control-label" }, [
                      _vm._v(_vm._s(_vm.$t("profile.oauth_redirect_url")))
                    ]),
                    _vm._v(" "),
                    _c("div", { staticClass: "col-md-7" }, [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.createForm.redirect,
                            expression: "createForm.redirect"
                          }
                        ],
                        staticClass: "form-control",
                        attrs: { type: "text", name: "redirect" },
                        domProps: { value: _vm.createForm.redirect },
                        on: {
                          keyup: function($event) {
                            if (
                              !$event.type.indexOf("key") &&
                              _vm._k(
                                $event.keyCode,
                                "enter",
                                13,
                                $event.key,
                                "Enter"
                              )
                            ) {
                              return null
                            }
                            return _vm.store($event)
                          },
                          input: function($event) {
                            if ($event.target.composing) {
                              return
                            }
                            _vm.$set(
                              _vm.createForm,
                              "redirect",
                              $event.target.value
                            )
                          }
                        }
                      }),
                      _vm._v(" "),
                      _c("span", { staticClass: "help-block" }, [
                        _vm._v(
                          "\n                                    " +
                            _vm._s(_vm.$t("profile.oauth_redirect_url_help")) +
                            "\n                                "
                        )
                      ])
                    ])
                  ])
                ]
              )
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "modal-footer" }, [
              _c(
                "button",
                {
                  staticClass: "btn btn-default",
                  attrs: { type: "button", "data-dismiss": "modal" }
                },
                [_vm._v(_vm._s(_vm.$t("firefly.close")))]
              ),
              _vm._v(" "),
              _c(
                "button",
                {
                  staticClass: "btn btn-primary",
                  attrs: { type: "button" },
                  on: { click: _vm.store }
                },
                [
                  _vm._v(
                    "\n                        " +
                      _vm._s(_vm.$t("firefly.create")) +
                      "\n                    "
                  )
                ]
              )
            ])
          ])
        ])
      ]
    ),
    _vm._v(" "),
    _c(
      "div",
      {
        staticClass: "modal fade",
        attrs: { id: "modal-edit-client", tabindex: "-1", role: "dialog" }
      },
      [
        _c("div", { staticClass: "modal-dialog" }, [
          _c("div", { staticClass: "modal-content" }, [
            _c("div", { staticClass: "modal-header" }, [
              _c(
                "button",
                {
                  staticClass: "close",
                  attrs: {
                    type: "button",
                    "data-dismiss": "modal",
                    "aria-hidden": "true"
                  }
                },
                [_vm._v("×")]
              ),
              _vm._v(" "),
              _c("h4", { staticClass: "modal-title" }, [
                _vm._v(
                  "\n                        " +
                    _vm._s(_vm.$t("profile.oauth_edit_client")) +
                    "\n                    "
                )
              ])
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "modal-body" }, [
              _vm.editForm.errors.length > 0
                ? _c("div", { staticClass: "alert alert-danger" }, [
                    _c("p", [
                      _c("strong", [_vm._v(_vm._s(_vm.$t("firefly.whoops")))]),
                      _vm._v(" " + _vm._s(_vm.$t("firefly.something_wrong")))
                    ]),
                    _vm._v(" "),
                    _c("br"),
                    _vm._v(" "),
                    _c(
                      "ul",
                      _vm._l(_vm.editForm.errors, function(error) {
                        return _c("li", [
                          _vm._v(
                            "\n                                " +
                              _vm._s(error) +
                              "\n                            "
                          )
                        ])
                      }),
                      0
                    )
                  ])
                : _vm._e(),
              _vm._v(" "),
              _c(
                "form",
                { staticClass: "form-horizontal", attrs: { role: "form" } },
                [
                  _c("div", { staticClass: "form-group" }, [
                    _c("label", { staticClass: "col-md-3 control-label" }, [
                      _vm._v(_vm._s(_vm.$t("firefly.name")))
                    ]),
                    _vm._v(" "),
                    _c("div", { staticClass: "col-md-7" }, [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.editForm.name,
                            expression: "editForm.name"
                          }
                        ],
                        staticClass: "form-control",
                        attrs: { id: "edit-client-name", type: "text" },
                        domProps: { value: _vm.editForm.name },
                        on: {
                          keyup: function($event) {
                            if (
                              !$event.type.indexOf("key") &&
                              _vm._k(
                                $event.keyCode,
                                "enter",
                                13,
                                $event.key,
                                "Enter"
                              )
                            ) {
                              return null
                            }
                            return _vm.update($event)
                          },
                          input: function($event) {
                            if ($event.target.composing) {
                              return
                            }
                            _vm.$set(_vm.editForm, "name", $event.target.value)
                          }
                        }
                      }),
                      _vm._v(" "),
                      _c("span", { staticClass: "help-block" }, [
                        _vm._v(
                          "\n                                    " +
                            _vm._s(_vm.$t("profile.oauth_name_help")) +
                            "\n                                "
                        )
                      ])
                    ])
                  ]),
                  _vm._v(" "),
                  _c("div", { staticClass: "form-group" }, [
                    _c("label", { staticClass: "col-md-3 control-label" }, [
                      _vm._v(_vm._s(_vm.$t("profile.oauth_redirect_url")))
                    ]),
                    _vm._v(" "),
                    _c("div", { staticClass: "col-md-7" }, [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.editForm.redirect,
                            expression: "editForm.redirect"
                          }
                        ],
                        staticClass: "form-control",
                        attrs: { type: "text", name: "redirect" },
                        domProps: { value: _vm.editForm.redirect },
                        on: {
                          keyup: function($event) {
                            if (
                              !$event.type.indexOf("key") &&
                              _vm._k(
                                $event.keyCode,
                                "enter",
                                13,
                                $event.key,
                                "Enter"
                              )
                            ) {
                              return null
                            }
                            return _vm.update($event)
                          },
                          input: function($event) {
                            if ($event.target.composing) {
                              return
                            }
                            _vm.$set(
                              _vm.editForm,
                              "redirect",
                              $event.target.value
                            )
                          }
                        }
                      }),
                      _vm._v(" "),
                      _c("span", { staticClass: "help-block" }, [
                        _vm._v(
                          "\n                                    " +
                            _vm._s(_vm.$t("profile.oauth_redirect_url_help")) +
                            "\n                                "
                        )
                      ])
                    ])
                  ])
                ]
              )
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "modal-footer" }, [
              _c(
                "button",
                {
                  staticClass: "btn btn-default",
                  attrs: { type: "button", "data-dismiss": "modal" }
                },
                [_vm._v(_vm._s(_vm.$t("firefly.close")))]
              ),
              _vm._v(" "),
              _c(
                "button",
                {
                  staticClass: "btn btn-primary",
                  attrs: { type: "button" },
                  on: { click: _vm.update }
                },
                [
                  _vm._v(
                    "\n                        " +
                      _vm._s(_vm.$t("firefly.save_changes")) +
                      "\n                    "
                  )
                ]
              )
            ])
          ])
        ])
      ]
    )
  ])
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=template&id=89c53f18&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=template&id=89c53f18&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", [
    _c("div", [
      _c("div", { staticClass: "box box-primary" }, [
        _c("div", { staticClass: "box-header with-border" }, [
          _c("h3", { staticClass: "box-title" }, [
            _vm._v(
              "\n                        " +
                _vm._s(_vm.$t("profile.personal_access_tokens")) +
                "\n                "
            )
          ])
        ]),
        _vm._v(" "),
        _c("div", { staticClass: "box-body" }, [
          _vm.tokens.length === 0
            ? _c("p", { staticClass: "m-b-none" }, [
                _vm._v(
                  "\n                    " +
                    _vm._s(_vm.$t("profile.no_personal_access_token")) +
                    "\n                "
                )
              ])
            : _vm._e(),
          _vm._v(" "),
          _vm.tokens.length > 0
            ? _c("table", { staticClass: "table table-borderless m-b-none" }, [
                _c("caption", [
                  _vm._v(_vm._s(_vm.$t("profile.personal_access_tokens")))
                ]),
                _vm._v(" "),
                _c("thead", [
                  _c("tr", [
                    _c("th", { attrs: { scope: "col" } }, [
                      _vm._v(_vm._s(_vm.$t("firefly.name")))
                    ]),
                    _vm._v(" "),
                    _c("th", { attrs: { scope: "col" } })
                  ])
                ]),
                _vm._v(" "),
                _c(
                  "tbody",
                  _vm._l(_vm.tokens, function(token) {
                    return _c("tr", [
                      _c(
                        "td",
                        { staticStyle: { "vertical-align": "middle" } },
                        [
                          _vm._v(
                            "\n                                " +
                              _vm._s(token.name) +
                              "\n                            "
                          )
                        ]
                      ),
                      _vm._v(" "),
                      _c(
                        "td",
                        { staticStyle: { "vertical-align": "middle" } },
                        [
                          _c(
                            "a",
                            {
                              staticClass: "action-link text-danger",
                              on: {
                                click: function($event) {
                                  return _vm.revoke(token)
                                }
                              }
                            },
                            [
                              _vm._v(
                                "\n                                    " +
                                  _vm._s(_vm.$t("firefly.delete")) +
                                  "\n                                "
                              )
                            ]
                          )
                        ]
                      )
                    ])
                  }),
                  0
                )
              ])
            : _vm._e()
        ]),
        _vm._v(" "),
        _c("div", { staticClass: "box-footer" }, [
          _c(
            "a",
            {
              staticClass: "action-link btn btn-success",
              on: { click: _vm.showCreateTokenForm }
            },
            [
              _vm._v(
                "\n                    " +
                  _vm._s(_vm.$t("profile.create_new_token")) +
                  "\n                "
              )
            ]
          )
        ])
      ])
    ]),
    _vm._v(" "),
    _c(
      "div",
      {
        staticClass: "modal fade",
        attrs: { id: "modal-create-token", tabindex: "-1", role: "dialog" }
      },
      [
        _c("div", { staticClass: "modal-dialog" }, [
          _c("div", { staticClass: "modal-content" }, [
            _c("div", { staticClass: "modal-header" }, [
              _c(
                "button",
                {
                  staticClass: "close",
                  attrs: {
                    type: "button",
                    "data-dismiss": "modal",
                    "aria-hidden": "true"
                  }
                },
                [_vm._v("×")]
              ),
              _vm._v(" "),
              _c("h4", { staticClass: "modal-title" }, [
                _vm._v(
                  "\n                        " +
                    _vm._s(_vm.$t("profile.create_token")) +
                    "\n                    "
                )
              ])
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "modal-body" }, [
              _vm.form.errors.length > 0
                ? _c("div", { staticClass: "alert alert-danger" }, [
                    _c("p", [
                      _c("strong", [_vm._v(_vm._s(_vm.$t("firefly.whoops")))]),
                      _vm._v(" " + _vm._s(_vm.$t("firefly.something_wrong")))
                    ]),
                    _vm._v(" "),
                    _c("br"),
                    _vm._v(" "),
                    _c(
                      "ul",
                      _vm._l(_vm.form.errors, function(error) {
                        return _c("li", [
                          _vm._v(
                            "\n                                " +
                              _vm._s(error) +
                              "\n                            "
                          )
                        ])
                      }),
                      0
                    )
                  ])
                : _vm._e(),
              _vm._v(" "),
              _c(
                "form",
                {
                  staticClass: "form-horizontal",
                  attrs: { role: "form" },
                  on: {
                    submit: function($event) {
                      $event.preventDefault()
                      return _vm.store($event)
                    }
                  }
                },
                [
                  _c("div", { staticClass: "form-group" }, [
                    _c("label", { staticClass: "col-md-4 control-label" }, [
                      _vm._v(_vm._s(_vm.$t("firefly.name")))
                    ]),
                    _vm._v(" "),
                    _c("div", { staticClass: "col-md-6" }, [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.form.name,
                            expression: "form.name"
                          }
                        ],
                        staticClass: "form-control",
                        attrs: {
                          id: "create-token-name",
                          type: "text",
                          name: "name"
                        },
                        domProps: { value: _vm.form.name },
                        on: {
                          input: function($event) {
                            if ($event.target.composing) {
                              return
                            }
                            _vm.$set(_vm.form, "name", $event.target.value)
                          }
                        }
                      })
                    ])
                  ]),
                  _vm._v(" "),
                  _vm.scopes.length > 0
                    ? _c("div", { staticClass: "form-group" }, [
                        _c("label", { staticClass: "col-md-4 control-label" }, [
                          _vm._v(_vm._s(_vm.$t("profile.scopes")))
                        ]),
                        _vm._v(" "),
                        _c(
                          "div",
                          { staticClass: "col-md-6" },
                          _vm._l(_vm.scopes, function(scope) {
                            return _c("div", [
                              _c("div", { staticClass: "checkbox" }, [
                                _c("label", [
                                  _c("input", {
                                    attrs: { type: "checkbox" },
                                    domProps: {
                                      checked: _vm.scopeIsAssigned(scope.id)
                                    },
                                    on: {
                                      click: function($event) {
                                        return _vm.toggleScope(scope.id)
                                      }
                                    }
                                  }),
                                  _vm._v(
                                    "\n\n                                                " +
                                      _vm._s(scope.id) +
                                      "\n                                        "
                                  )
                                ])
                              ])
                            ])
                          }),
                          0
                        )
                      ])
                    : _vm._e()
                ]
              )
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "modal-footer" }, [
              _c(
                "button",
                {
                  staticClass: "btn btn-default",
                  attrs: { type: "button", "data-dismiss": "modal" }
                },
                [_vm._v(_vm._s(_vm.$t("firefly.close")))]
              ),
              _vm._v(" "),
              _c(
                "button",
                {
                  staticClass: "btn btn-primary",
                  attrs: { type: "button" },
                  on: { click: _vm.store }
                },
                [
                  _vm._v(
                    "\n                        " +
                      _vm._s(_vm.$t("firefly.create")) +
                      "\n                    "
                  )
                ]
              )
            ])
          ])
        ])
      ]
    ),
    _vm._v(" "),
    _c(
      "div",
      {
        staticClass: "modal fade",
        attrs: { id: "modal-access-token", tabindex: "-1", role: "dialog" }
      },
      [
        _c("div", { staticClass: "modal-dialog" }, [
          _c("div", { staticClass: "modal-content" }, [
            _c("div", { staticClass: "modal-header" }, [
              _c(
                "button",
                {
                  staticClass: "close",
                  attrs: {
                    type: "button",
                    "data-dismiss": "modal",
                    "aria-hidden": "true"
                  }
                },
                [_vm._v("×")]
              ),
              _vm._v(" "),
              _c("h4", { staticClass: "modal-title" }, [
                _vm._v(
                  "\n                        " +
                    _vm._s(_vm.$t("profile.personal_access_token")) +
                    "\n                    "
                )
              ])
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "modal-body" }, [
              _c("p", [
                _vm._v(
                  "\n                        " +
                    _vm._s(
                      _vm.$t("profile.personal_access_token_explanation")
                    ) +
                    "                            \n                    "
                )
              ]),
              _vm._v(" "),
              _c("pre", [
                _c(
                  "textarea",
                  {
                    staticClass: "form-control",
                    staticStyle: { width: "100%" },
                    attrs: { id: "tokenHidden", rows: "20" }
                  },
                  [_vm._v(_vm._s(_vm.accessToken))]
                )
              ])
            ]),
            _vm._v(" "),
            _c("div", { staticClass: "modal-footer" }, [
              _c(
                "button",
                {
                  staticClass: "btn btn-default",
                  attrs: { type: "button", "data-dismiss": "modal" }
                },
                [_vm._v(_vm._s(_vm.$t("firefly.close")))]
              )
            ])
          ])
        ])
      ]
    )
  ])
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=template&id=73401752&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=template&id=73401752&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", [
    _c("div", { staticClass: "row" }, [
      _c(
        "div",
        { staticClass: "col-lg-8 col-lg-offset-2 col-md-12 col-sm-12" },
        [_c("passport-clients")],
        1
      )
    ]),
    _vm._v(" "),
    _c("div", { staticClass: "row" }, [
      _c(
        "div",
        { staticClass: "col-lg-8 col-lg-offset-2 col-md-12 col-sm-12" },
        [_c("passport-authorized-clients")],
        1
      )
    ]),
    _vm._v(" "),
    _c("div", { staticClass: "row" }, [
      _c(
        "div",
        { staticClass: "col-lg-8 col-lg-offset-2 col-md-12 col-sm-12" },
        [_c("passport-personal-access-tokens")],
        1
      )
    ])
  ])
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return normalizeComponent; });
/* globals __VUE_SSR_CONTEXT__ */

// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).
// This module is a runtime utility for cleaner component module output and will
// be included in the final webpack user bundle.

function normalizeComponent (
  scriptExports,
  render,
  staticRenderFns,
  functionalTemplate,
  injectStyles,
  scopeId,
  moduleIdentifier, /* server only */
  shadowMode /* vue-cli only */
) {
  // Vue.extend constructor export interop
  var options = typeof scriptExports === 'function'
    ? scriptExports.options
    : scriptExports

  // render functions
  if (render) {
    options.render = render
    options.staticRenderFns = staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = 'data-v-' + scopeId
  }

  var hook
  if (moduleIdentifier) { // server build
    hook = function (context) {
      // 2.3 injection
      context =
        context || // cached call
        (this.$vnode && this.$vnode.ssrContext) || // stateful
        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inferrence
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = shadowMode
      ? function () {
        injectStyles.call(
          this,
          (options.functional ? this.parent : this).$root.$options.shadowRoot
        )
      }
      : injectStyles
  }

  if (hook) {
    if (options.functional) {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functional component in vue file
      var originalRender = options.render
      options.render = function renderWithStyleInjection (h, context) {
        hook.call(context)
        return originalRender(h, context)
      }
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate
      options.beforeCreate = existing
        ? [].concat(existing, hook)
        : [hook]
    }
  }

  return {
    exports: scriptExports,
    options: options
  }
}


/***/ }),

/***/ "./resources/assets/js/bootstrap.js":
/*!******************************************!*\
  !*** ./resources/assets/js/bootstrap.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*
 * bootstrap.js
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/*
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
window.axios = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

var token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
  console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/***/ }),

/***/ "./resources/assets/js/components/passport/AuthorizedClients.vue":
/*!***********************************************************************!*\
  !*** ./resources/assets/js/components/passport/AuthorizedClients.vue ***!
  \***********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AuthorizedClients_vue_vue_type_template_id_2ee9fe67_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AuthorizedClients.vue?vue&type=template&id=2ee9fe67&scoped=true& */ "./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=template&id=2ee9fe67&scoped=true&");
/* harmony import */ var _AuthorizedClients_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AuthorizedClients.vue?vue&type=script&lang=js& */ "./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _AuthorizedClients_vue_vue_type_style_index_0_id_2ee9fe67_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css& */ "./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AuthorizedClients_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AuthorizedClients_vue_vue_type_template_id_2ee9fe67_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _AuthorizedClients_vue_vue_type_template_id_2ee9fe67_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "2ee9fe67",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/assets/js/components/passport/AuthorizedClients.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=script&lang=js&":
/*!************************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib??ref--4-0!../../../../../node_modules/vue-loader/lib??vue-loader-options!./AuthorizedClients.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css&":
/*!********************************************************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css& ***!
  \********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_style_index_0_id_2ee9fe67_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader!../../../../../node_modules/css-loader??ref--5-1!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/postcss-loader/src??ref--5-2!../../../../../node_modules/vue-loader/lib??vue-loader-options!./AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css& */ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=style&index=0&id=2ee9fe67&scoped=true&lang=css&");
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_style_index_0_id_2ee9fe67_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_style_index_0_id_2ee9fe67_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_style_index_0_id_2ee9fe67_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_style_index_0_id_2ee9fe67_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_style_index_0_id_2ee9fe67_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=template&id=2ee9fe67&scoped=true&":
/*!******************************************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=template&id=2ee9fe67&scoped=true& ***!
  \******************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_template_id_2ee9fe67_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib??vue-loader-options!./AuthorizedClients.vue?vue&type=template&id=2ee9fe67&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/AuthorizedClients.vue?vue&type=template&id=2ee9fe67&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_template_id_2ee9fe67_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthorizedClients_vue_vue_type_template_id_2ee9fe67_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./resources/assets/js/components/passport/Clients.vue":
/*!*************************************************************!*\
  !*** ./resources/assets/js/components/passport/Clients.vue ***!
  \*************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Clients_vue_vue_type_template_id_5d1d7d82_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Clients.vue?vue&type=template&id=5d1d7d82&scoped=true& */ "./resources/assets/js/components/passport/Clients.vue?vue&type=template&id=5d1d7d82&scoped=true&");
/* harmony import */ var _Clients_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Clients.vue?vue&type=script&lang=js& */ "./resources/assets/js/components/passport/Clients.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _Clients_vue_vue_type_style_index_0_id_5d1d7d82_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css& */ "./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Clients_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Clients_vue_vue_type_template_id_5d1d7d82_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _Clients_vue_vue_type_template_id_5d1d7d82_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "5d1d7d82",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/assets/js/components/passport/Clients.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./resources/assets/js/components/passport/Clients.vue?vue&type=script&lang=js&":
/*!**************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/Clients.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib??ref--4-0!../../../../../node_modules/vue-loader/lib??vue-loader-options!./Clients.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/Clients.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css&":
/*!**********************************************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css& ***!
  \**********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_style_index_0_id_5d1d7d82_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader!../../../../../node_modules/css-loader??ref--5-1!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/postcss-loader/src??ref--5-2!../../../../../node_modules/vue-loader/lib??vue-loader-options!./Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css& */ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/Clients.vue?vue&type=style&index=0&id=5d1d7d82&scoped=true&lang=css&");
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_style_index_0_id_5d1d7d82_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_style_index_0_id_5d1d7d82_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_style_index_0_id_5d1d7d82_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_style_index_0_id_5d1d7d82_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_style_index_0_id_5d1d7d82_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./resources/assets/js/components/passport/Clients.vue?vue&type=template&id=5d1d7d82&scoped=true&":
/*!********************************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/Clients.vue?vue&type=template&id=5d1d7d82&scoped=true& ***!
  \********************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_template_id_5d1d7d82_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib??vue-loader-options!./Clients.vue?vue&type=template&id=5d1d7d82&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/Clients.vue?vue&type=template&id=5d1d7d82&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_template_id_5d1d7d82_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Clients_vue_vue_type_template_id_5d1d7d82_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./resources/assets/js/components/passport/PersonalAccessTokens.vue":
/*!**************************************************************************!*\
  !*** ./resources/assets/js/components/passport/PersonalAccessTokens.vue ***!
  \**************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _PersonalAccessTokens_vue_vue_type_template_id_89c53f18_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PersonalAccessTokens.vue?vue&type=template&id=89c53f18&scoped=true& */ "./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=template&id=89c53f18&scoped=true&");
/* harmony import */ var _PersonalAccessTokens_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PersonalAccessTokens.vue?vue&type=script&lang=js& */ "./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _PersonalAccessTokens_vue_vue_type_style_index_0_id_89c53f18_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css& */ "./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _PersonalAccessTokens_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _PersonalAccessTokens_vue_vue_type_template_id_89c53f18_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _PersonalAccessTokens_vue_vue_type_template_id_89c53f18_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "89c53f18",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/assets/js/components/passport/PersonalAccessTokens.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib??ref--4-0!../../../../../node_modules/vue-loader/lib??vue-loader-options!./PersonalAccessTokens.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css&":
/*!***********************************************************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css& ***!
  \***********************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_style_index_0_id_89c53f18_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader!../../../../../node_modules/css-loader??ref--5-1!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/postcss-loader/src??ref--5-2!../../../../../node_modules/vue-loader/lib??vue-loader-options!./PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css& */ "./node_modules/style-loader/index.js!./node_modules/css-loader/index.js?!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/src/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=style&index=0&id=89c53f18&scoped=true&lang=css&");
/* harmony import */ var _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_style_index_0_id_89c53f18_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_style_index_0_id_89c53f18_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_style_index_0_id_89c53f18_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_style_index_0_id_89c53f18_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_style_loader_index_js_node_modules_css_loader_index_js_ref_5_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_5_2_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_style_index_0_id_89c53f18_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=template&id=89c53f18&scoped=true&":
/*!*********************************************************************************************************************!*\
  !*** ./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=template&id=89c53f18&scoped=true& ***!
  \*********************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_template_id_89c53f18_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib??vue-loader-options!./PersonalAccessTokens.vue?vue&type=template&id=89c53f18&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/passport/PersonalAccessTokens.vue?vue&type=template&id=89c53f18&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_template_id_89c53f18_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalAccessTokens_vue_vue_type_template_id_89c53f18_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./resources/assets/js/components/profile/ProfileOptions.vue":
/*!*******************************************************************!*\
  !*** ./resources/assets/js/components/profile/ProfileOptions.vue ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ProfileOptions_vue_vue_type_template_id_73401752_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ProfileOptions.vue?vue&type=template&id=73401752&scoped=true& */ "./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=template&id=73401752&scoped=true&");
/* harmony import */ var _ProfileOptions_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ProfileOptions.vue?vue&type=script&lang=js& */ "./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ProfileOptions_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ProfileOptions_vue_vue_type_template_id_73401752_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _ProfileOptions_vue_vue_type_template_id_73401752_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "73401752",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/assets/js/components/profile/ProfileOptions.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=script&lang=js&":
/*!********************************************************************************************!*\
  !*** ./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileOptions_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib??ref--4-0!../../../../../node_modules/vue-loader/lib??vue-loader-options!./ProfileOptions.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_4_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileOptions_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=template&id=73401752&scoped=true&":
/*!**************************************************************************************************************!*\
  !*** ./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=template&id=73401752&scoped=true& ***!
  \**************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileOptions_vue_vue_type_template_id_73401752_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib??vue-loader-options!./ProfileOptions.vue?vue&type=template&id=73401752&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/assets/js/components/profile/ProfileOptions.vue?vue&type=template&id=73401752&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileOptions_vue_vue_type_template_id_73401752_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileOptions_vue_vue_type_template_id_73401752_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./resources/assets/js/i18n.js":
/*!*************************************!*\
  !*** ./resources/assets/js/i18n.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// Create VueI18n instance with options
module.exports = new vuei18n({
  locale: document.documentElement.lang,
  // set locale
  fallbackLocale: 'en',
  messages: {
    'cs': __webpack_require__(/*! ./locales/cs.json */ "./resources/assets/js/locales/cs.json"),
    'de': __webpack_require__(/*! ./locales/de.json */ "./resources/assets/js/locales/de.json"),
    'en': __webpack_require__(/*! ./locales/en.json */ "./resources/assets/js/locales/en.json"),
    'es': __webpack_require__(/*! ./locales/es.json */ "./resources/assets/js/locales/es.json"),
    'el': __webpack_require__(/*! ./locales/el.json */ "./resources/assets/js/locales/el.json"),
    'fr': __webpack_require__(/*! ./locales/fr.json */ "./resources/assets/js/locales/fr.json"),
    'hu': __webpack_require__(/*! ./locales/hu.json */ "./resources/assets/js/locales/hu.json"),
    'id': __webpack_require__(/*! ./locales/id.json */ "./resources/assets/js/locales/id.json"),
    'it': __webpack_require__(/*! ./locales/it.json */ "./resources/assets/js/locales/it.json"),
    'nl': __webpack_require__(/*! ./locales/nl.json */ "./resources/assets/js/locales/nl.json"),
    'no': __webpack_require__(/*! ./locales/no.json */ "./resources/assets/js/locales/no.json"),
    'pl': __webpack_require__(/*! ./locales/pl.json */ "./resources/assets/js/locales/pl.json"),
    'fi': __webpack_require__(/*! ./locales/fi.json */ "./resources/assets/js/locales/fi.json"),
    'pt-br': __webpack_require__(/*! ./locales/pt-br.json */ "./resources/assets/js/locales/pt-br.json"),
    'ro': __webpack_require__(/*! ./locales/ro.json */ "./resources/assets/js/locales/ro.json"),
    'ru': __webpack_require__(/*! ./locales/ru.json */ "./resources/assets/js/locales/ru.json"),
    'zh': __webpack_require__(/*! ./locales/zh.json */ "./resources/assets/js/locales/zh.json"),
    'zh-tw': __webpack_require__(/*! ./locales/zh-tw.json */ "./resources/assets/js/locales/zh-tw.json"),
    'zh-cn': __webpack_require__(/*! ./locales/zh-cn.json */ "./resources/assets/js/locales/zh-cn.json"),
    'sv': __webpack_require__(/*! ./locales/sv.json */ "./resources/assets/js/locales/sv.json"),
    'vi': __webpack_require__(/*! ./locales/vi.json */ "./resources/assets/js/locales/vi.json")
  }
});

/***/ }),

/***/ "./resources/assets/js/locales/cs.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/cs.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Jak to jde?\",\"flash_error\":\"Chyba!\",\"flash_success\":\"Úspěšně dokončeno!\",\"close\":\"Zavřít\",\"split_transaction_title\":\"Popis rozúčtování\",\"errors_submission\":\"There was something wrong with your submission. Please check out the errors below.\",\"split\":\"Rozdělit\",\"transaction_journal_information\":\"Informace o transakci\",\"no_budget_pointer\":\"Zdá se, že zatím nemáte žádné rozpočty. Na stránce <a href=\\\":link\\\">rozpočty</a> byste nějaké měli vytvořit. Rozpočty mohou pomoci udržet si přehled ve výdajích.\",\"source_account\":\"Zdrojový účet\",\"hidden_fields_preferences\":\"You can enable more transaction options in your <a href=\\\"/preferences\\\">settings</a>.\",\"destination_account\":\"Cílový účet\",\"add_another_split\":\"Přidat další rozúčtování\",\"submission\":\"Submission\",\"create_another\":\"After storing, return here to create another one.\",\"reset_after\":\"Reset form after submission\",\"submit\":\"Odeslat\",\"amount\":\"Částka\",\"date\":\"Datum\",\"tags\":\"Štítky\",\"no_budget\":\"(žádný rozpočet)\",\"category\":\"Kategorie\",\"attachments\":\"Přílohy\",\"notes\":\"Poznámky\",\"update_transaction\":\"Update transaction\",\"after_update_create_another\":\"After updating, return here to continue editing.\",\"store_as_new\":\"Store as a new transaction instead of updating.\",\"split_title_help\":\"Pokud vytvoříte rozúčtování, je třeba, aby zde byl celkový popis pro všechna rozúčtování dané transakce.\",\"none_in_select_list\":\"(žádné)\",\"no_piggy_bank\":\"(žádná pokladnička)\",\"description\":\"Popis\",\"split_transaction_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"destination_account_reconciliation\":\"You can't edit the destination account of a reconciliation transaction.\",\"source_account_reconciliation\":\"You can't edit the source account of a reconciliation transaction.\",\"budget\":\"Rozpočet\",\"you_create_withdrawal\":\"You're creating a withdrawal.\",\"you_create_transfer\":\"You're creating a transfer.\",\"you_create_deposit\":\"You're creating a deposit.\"},\"form\":{\"interest_date\":\"Úrokové datum\",\"book_date\":\"Datum rezervace\",\"process_date\":\"Datum zpracování\",\"due_date\":\"Datum splatnosti\",\"foreign_amount\":\"Částka v cizí měně\",\"payment_date\":\"Datum zaplacení\",\"invoice_date\":\"Datum vystavení\",\"internal_reference\":\"Interní reference\"},\"config\":{\"html_language\":\"cs\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/de.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/de.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Überblick\",\"flash_error\":\"Fehler!\",\"flash_success\":\"Geschafft!\",\"close\":\"Schließen\",\"split_transaction_title\":\"Beschreibung der Splittbuchung\",\"errors_submission\":\"Problem bei der Übermittlung. Bitte überprüfen Sie die nachfolgenden Fehler.\",\"split\":\"Teilen\",\"transaction_journal_information\":\"Transaktionsinformationen\",\"no_budget_pointer\":\"Sie scheinen noch keine Kostenrahmen festgelegt zu haben. Sie sollten einige davon auf der Seite <a href=\\\"/budgets\\\">„Kostenrahmen”</a> anlegen. Kostenrahmen können Ihnen dabei helfen, den Überblick über die Ausgaben zu behalten.\",\"source_account\":\"Quellkonto\",\"hidden_fields_preferences\":\"Sie können weitere Buchungsoptionen in Ihren <a href=\\\"/preferences\\\">Einstellungen</a> aktivieren.\",\"destination_account\":\"Zielkonto\",\"add_another_split\":\"Eine weitere Aufteilung hinzufügen\",\"submission\":\"Übermittlung\",\"create_another\":\"Nach dem Speichern hierher zurückkehren, um ein weiteres zu erstellen.\",\"reset_after\":\"Formular nach der Übermittlung zurücksetzen\",\"submit\":\"Absenden\",\"amount\":\"Betrag\",\"date\":\"Datum\",\"tags\":\"Schlagwörter\",\"no_budget\":\"(kein Budget)\",\"category\":\"Kategorie\",\"attachments\":\"Anhänge\",\"notes\":\"Notizen\",\"update_transaction\":\"Buchung aktualisieren\",\"after_update_create_another\":\"Nach dem Aktualisieren hierher zurückkehren, um weiter zu bearbeiten.\",\"store_as_new\":\"Als neue Buchung speichern statt zu aktualisieren.\",\"split_title_help\":\"Wenn Sie eine Splittbuchung anlegen, muss es eine eindeutige Beschreibung für alle Aufteilungen der Buchhaltung geben.\",\"none_in_select_list\":\"(Keine)\",\"no_piggy_bank\":\"(kein Sparschwein)\",\"description\":\"Beschreibung\",\"split_transaction_title_help\":\"Wenn Sie eine Splittbuchung anlegen, muss es eine eindeutige Beschreibung für alle Aufteilungen der Buchung geben.\",\"destination_account_reconciliation\":\"Sie können das Zielkonto einer Kontenausgleichsbuchung nicht bearbeiten.\",\"source_account_reconciliation\":\"Sie können das Quellkonto einer Kontenausgleichsbuchung nicht bearbeiten.\",\"budget\":\"Budget\",\"you_create_withdrawal\":\"Sie haben eine Auszahlung erstellt.\",\"you_create_transfer\":\"Sie haben eine Buchung erstellt.\",\"you_create_deposit\":\"Sie haben eine Einzahlung erstellt.\"},\"form\":{\"interest_date\":\"Zinstermin\",\"book_date\":\"Buchungsdatum\",\"process_date\":\"Bearbeitungsdatum\",\"due_date\":\"Fälligkeitstermin\",\"foreign_amount\":\"Ausländischer Betrag\",\"payment_date\":\"Zahlungsdatum\",\"invoice_date\":\"Rechnungsdatum\",\"internal_reference\":\"Interner Verweis\"},\"config\":{\"html_language\":\"de\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/el.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/el.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Τι παίζει;\",\"flash_error\":\"Σφάλμα!\",\"flash_success\":\"Επιτυχία!\",\"close\":\"Κλείσιμο\",\"split_transaction_title\":\"Περιγραφή της συναλλαγής με διαχωρισμό\",\"errors_submission\":\"Υπήρξε κάποιο λάθος με την υποβολή σας. Ελέγξτε τα παρακάτω σφάλματα.\",\"split\":\"Διαχωρισμός\",\"transaction_journal_information\":\"Πληροφορίες συναλλαγής\",\"no_budget_pointer\":\"Φαίνεται πως δεν έχετε ορίσει προϋπολογισμούς ακόμη. Πρέπει να δημιουργήσετε κάποιον στη σελίδα <a href=\\\"/budgets\\\">προϋπολογισμών</a>. Οι προϋπολογισμοί σας βοηθούν να επιβλέπετε τις δαπάνες σας.\",\"source_account\":\"Λογαριασμός προέλευσης\",\"hidden_fields_preferences\":\"Μπορείτε να ενεργοποιήσετε περισσότερες επιλογές συναλλαγών στις <a href=\\\"/preferences\\\">ρυθμίσεις</a>.\",\"destination_account\":\"Λογαριασμός προορισμού\",\"add_another_split\":\"Προσθήκη ενός ακόμα διαχωρισμού\",\"submission\":\"Υποβολή\",\"create_another\":\"Μετά την αποθήκευση, επιστρέψτε εδώ για να δημιουργήσετε ακόμη ένα.\",\"reset_after\":\"Επαναφορά φόρμας μετά την υποβολή\",\"submit\":\"Υποβολή\",\"amount\":\"Ποσό\",\"date\":\"Ημερομηνία\",\"tags\":\"Ετικέτες\",\"no_budget\":\"(χωρίς προϋπολογισμό)\",\"category\":\"Κατηγορία\",\"attachments\":\"Συνημμένα\",\"notes\":\"Σημειώσεις\",\"update_transaction\":\"Ενημέρωση συναλλαγής\",\"after_update_create_another\":\"Μετά την ενημέρωση, επιστρέψτε εδώ για να συνεχίσετε την επεξεργασία.\",\"store_as_new\":\"Αποθήκευση ως νέα συναλλαγή αντί για ενημέρωση.\",\"split_title_help\":\"Εάν δημιουργήσετε μια διαχωρισμένη συναλλαγή, πρέπει να υπάρχει μια καθολική περιγραφή για όλους τους διαχωρισμούς της συναλλαγής.\",\"none_in_select_list\":\"(τίποτα)\",\"no_piggy_bank\":\"(χωρίς κουμπαρά)\",\"description\":\"Περιγραφή\",\"split_transaction_title_help\":\"Εάν δημιουργήσετε μια διαχωρισμένη συναλλαγή, πρέπει να υπάρχει μια καθολική περιγραφή για όλους τους διαχωρισμούς της συναλλαγής.\",\"destination_account_reconciliation\":\"Δεν μπορείτε να τροποποιήσετε τον λογαριασμό προορισμού σε μια συναλλαγή τακτοποίησης.\",\"source_account_reconciliation\":\"Δεν μπορείτε να τροποποιήσετε τον λογαριασμό προέλευσης σε μια συναλλαγή τακτοποίησης.\",\"budget\":\"Προϋπολογισμός\",\"you_create_withdrawal\":\"Δημιουργείτε μια ανάληψη.\",\"you_create_transfer\":\"Δημιουργείτε μια μεταφορά.\",\"you_create_deposit\":\"Δημιουργείτε μια κατάθεση.\"},\"form\":{\"interest_date\":\"Ημερομηνία τοκισμού\",\"book_date\":\"Ημερομηνία εγγραφής\",\"process_date\":\"Ημερομηνία επεξεργασίας\",\"due_date\":\"Ημερομηνία προθεσμίας\",\"foreign_amount\":\"Ποσό σε ξένο νόμισμα\",\"payment_date\":\"Ημερομηνία πληρωμής\",\"invoice_date\":\"Ημερομηνία τιμολόγησης\",\"internal_reference\":\"Εσωτερική αναφορά\"},\"config\":{\"html_language\":\"el\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/en.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/en.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, profile, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"What's playing?\",\"flash_error\":\"Error!\",\"flash_success\":\"Success!\",\"create\":\"Create\",\"edit\":\"Edit\",\"delete\":\"Delete\",\"save_changes\":\"Save Changes\",\"name\":\"Name\",\"close\":\"Close\",\"whoops\":\" Whoops!\",\"something_wrong\":\"Something went wrong!\",\"try_again\":\"Something went wrong. Please try again.\",\"split_transaction_title\":\"Description of the split transaction\",\"errors_submission\":\"There was something wrong with your submission. Please check out the errors below.\",\"split\":\"Split\",\"transaction_journal_information\":\"Transaction information\",\"no_budget_pointer\":\"You seem to have no budgets yet. You should create some on the <a href=\\\"/budgets\\\">budgets</a>-page. Budgets can help you keep track of expenses.\",\"source_account\":\"Source account\",\"hidden_fields_preferences\":\"You can enable more transaction options in your <a href=\\\"/preferences\\\">settings</a>.\",\"destination_account\":\"Destination account\",\"add_another_split\":\"Add another split\",\"submission\":\"Submission\",\"create_another\":\"After storing, return here to create another one.\",\"reset_after\":\"Reset form after submission\",\"submit\":\"Submit\",\"amount\":\"Amount\",\"date\":\"Date\",\"tags\":\"Tags\",\"no_budget\":\"(no budget)\",\"category\":\"Category\",\"attachments\":\"Attachments\",\"notes\":\"Notes\",\"update_transaction\":\"Update transaction\",\"after_update_create_another\":\"After updating, return here to continue editing.\",\"store_as_new\":\"Store as a new transaction instead of updating.\",\"split_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"none_in_select_list\":\"(none)\",\"no_piggy_bank\":\"(no piggy bank)\",\"description\":\"Description\",\"split_transaction_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"destination_account_reconciliation\":\"You can't edit the destination account of a reconciliation transaction.\",\"source_account_reconciliation\":\"You can't edit the source account of a reconciliation transaction.\",\"budget\":\"Budget\",\"you_create_withdrawal\":\"You're creating a withdrawal.\",\"you_create_transfer\":\"You're creating a transfer.\",\"you_create_deposit\":\"You're creating a deposit.\"},\"form\":{\"interest_date\":\"Interest date\",\"book_date\":\"Book date\",\"process_date\":\"Processing date\",\"due_date\":\"Due date\",\"foreign_amount\":\"Foreign amount\",\"payment_date\":\"Payment date\",\"invoice_date\":\"Invoice date\",\"internal_reference\":\"Internal reference\"},\"config\":{\"html_language\":\"en\"},\"profile\":{\"oauth_clients\":\"OAuth Clients\",\"oauth_no_clients\":\"You have not created any OAuth clients.\",\"oauth_clients_header\":\"Clients\",\"oauth_client_id\":\"Client ID\",\"oauth_client_name\":\"Name\",\"oauth_client_secret\":\"Secret\",\"oauth_create_new_client\":\"Create New Client\",\"oauth_create_client\":\"Create Client\",\"oauth_edit_client\":\"Edit Client\",\"oauth_name_help\":\"Something your users will recognize and trust.\",\"oauth_redirect_url\":\"Redirect URL\",\"oauth_redirect_url_help\":\"Your application's authorization callback URL.\",\"authorized_apps\":\"Authorized Applications\",\"authorized_clients\":\"Authorized clients\",\"scopes\":\"Scopes\",\"revoke\":\"Revoke\",\"personal_access_tokens\":\"Personal Access Tokens\",\"personal_access_token\":\"Personal Access Token\",\"personal_access_token_explanation\":\"Here is your new personal access token. This is the only time it will be shown so don't lose it! You may now use this token to make API requests.\",\"no_personal_access_token\":\"You have not created any personal access tokens.\",\"create_new_token\":\"Create New Token\",\"create_token\":\"Create Token\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/es.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/es.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"¿Qué está pasando?\",\"flash_error\":\"¡Error!\",\"flash_success\":\"¡Operación correcta!\",\"close\":\"Cerrar\",\"split_transaction_title\":\"Descripción de la transacción dividida\",\"errors_submission\":\"Hubo algo malo con su envío. Por favor, revise los errores de abajo.\",\"split\":\"Separar\",\"transaction_journal_information\":\"Información de transacción\",\"no_budget_pointer\":\"Parece que aún no tiene presupuestos. Debe crear algunos en la página <a href=\\\"/budgets\\\">presupuestos</a>. Los presupuestos pueden ayudarle a realizar un seguimiento de los gastos.\",\"source_account\":\"Cuenta origen\",\"hidden_fields_preferences\":\"Puede habilitar más opciones de transacción en sus <a href=\\\"/preferences\\\">ajustes </a>.\",\"destination_account\":\"Cuenta destino\",\"add_another_split\":\"Añadir otra división\",\"submission\":\"Envío\",\"create_another\":\"Después de guardar, vuelve aquí para crear otro.\",\"reset_after\":\"Restablecer formulario después del envío\",\"submit\":\"Enviar\",\"amount\":\"Cantidad\",\"date\":\"Fecha\",\"tags\":\"Etiquetas\",\"no_budget\":\"(sin presupuesto)\",\"category\":\"Categoria\",\"attachments\":\"Archivos adjuntos\",\"notes\":\"Notas\",\"update_transaction\":\"Actualizar transacción\",\"after_update_create_another\":\"Después de actualizar, vuelve aquí para continuar editando.\",\"store_as_new\":\"Almacenar como una nueva transacción en lugar de actualizar.\",\"split_title_help\":\"Si crea una transacción dividida, debe haber una descripción global para todos los fragmentos de la transacción.\",\"none_in_select_list\":\"(ninguno)\",\"no_piggy_bank\":\"(sin alcancía)\",\"description\":\"Descripción\",\"split_transaction_title_help\":\"Si crea una transacción dividida, debe existir una descripción global para todas las divisiones de la transacción.\",\"destination_account_reconciliation\":\"No puede editar la cuenta de destino de una transacción de reconciliación.\",\"source_account_reconciliation\":\"No puede editar la cuenta de origen de una transacción de reconciliación.\",\"budget\":\"Presupuesto\",\"you_create_withdrawal\":\"Está creando un retiro.\",\"you_create_transfer\":\"Está creando una transferencia.\",\"you_create_deposit\":\"Está creando un depósito.\"},\"form\":{\"interest_date\":\"Fecha de interés\",\"book_date\":\"Fecha de registro\",\"process_date\":\"Fecha de procesamiento\",\"due_date\":\"Fecha de vencimiento\",\"foreign_amount\":\"Cantidad extranjera\",\"payment_date\":\"Fecha de pago\",\"invoice_date\":\"Fecha de la factura\",\"internal_reference\":\"Referencia interna\"},\"config\":{\"html_language\":\"es\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/fi.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/fi.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Mitä kuuluu?\",\"flash_error\":\"Virhe!\",\"flash_success\":\"Valmista tuli!\",\"close\":\"Sulje\",\"split_transaction_title\":\"Jaetun tapahtuman kuvaus\",\"errors_submission\":\"Lomakkeen tiedoissa oli puutteita - alta löydät listan puutteista.\",\"split\":\"Jaa\",\"transaction_journal_information\":\"Tapahtumatiedot\",\"no_budget_pointer\":\"Sinulla ei näyttäisi olevan vielä yhtään budjettia. Sinun kannattaisi luoda niitä <a href=\\\"/budgets\\\">budjetit</a>-sivulla. Budjetit voivat auttaa sinua pitämään kirjaa kuluistasi.\",\"source_account\":\"Lähdetili\",\"hidden_fields_preferences\":\"Voit aktivoida lisää tapahtumavalintoja <a href=\\\"/preferences\\\">asetuksissa</a>.\",\"destination_account\":\"Kohdetili\",\"add_another_split\":\"Lisää tapahtumaan uusi osa\",\"submission\":\"Vahvistus\",\"create_another\":\"Tallennuksen jälkeen, palaa takaisin luomaan uusi tapahtuma.\",\"reset_after\":\"Tyhjennä lomake lähetyksen jälkeen\",\"submit\":\"Vahvista\",\"amount\":\"Summa\",\"date\":\"Päivämäärä\",\"tags\":\"Tägit\",\"no_budget\":\"(ei budjettia)\",\"category\":\"Kategoria\",\"attachments\":\"Liitteet\",\"notes\":\"Muistiinpanot\",\"update_transaction\":\"Päivitä tapahtuma\",\"after_update_create_another\":\"Päivityksen jälkeen, palaa takaisin jatkamaan muokkausta.\",\"store_as_new\":\"Tallenna uutena tapahtumana päivityksen sijaan.\",\"split_title_help\":\"Jos luot jaetun tapahtuman, kokonaisuudelle tarvitaan nimi.\",\"none_in_select_list\":\"(ei mitään)\",\"no_piggy_bank\":\"(ei säästöpossu)\",\"description\":\"Kuvaus\",\"split_transaction_title_help\":\"Jos luot jaetun tapahtuman, kokonaisuudelle tarvitaan nimi.\",\"destination_account_reconciliation\":\"Et voi muokata täsmäytystapahtuman kohdetiliä.\",\"source_account_reconciliation\":\"Et voi muokata täsmäytystapahtuman lähdetiliä.\",\"budget\":\"Budjetti\",\"you_create_withdrawal\":\"Olet luomassa nostoa.\",\"you_create_transfer\":\"Olet luomassa siirtoa.\",\"you_create_deposit\":\"Olet luomassa talletusta.\"},\"form\":{\"interest_date\":\"Korkopäivä\",\"book_date\":\"Kirjauspäivä\",\"process_date\":\"Käsittelypäivä\",\"due_date\":\"Eräpäivä\",\"foreign_amount\":\"Ulkomaan summa\",\"payment_date\":\"Maksupäivä\",\"invoice_date\":\"Laskun päivämäärä\",\"internal_reference\":\"Sisäinen viite\"},\"config\":{\"html_language\":\"fi\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/fr.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/fr.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, profile, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Que se passe-t-il ?\",\"flash_error\":\"Erreur !\",\"flash_success\":\"Super !\",\"close\":\"Fermer\",\"split_transaction_title\":\"Description de l'opération ventilée\",\"errors_submission\":\"Certaines informations ne sont pas correctes dans votre formulaire. Veuillez vérifier les erreurs ci-dessous.\",\"split\":\"Ventiler\",\"transaction_journal_information\":\"Informations sur les opérations\",\"no_budget_pointer\":\"Vous semblez n’avoir encore aucun budget. Vous devriez en créer un sur la page des <a href=\\\"/budgets\\\">budgets</a>. Les budgets peuvent vous aider à garder une trace des dépenses.\",\"source_account\":\"Compte source\",\"hidden_fields_preferences\":\"Vous pouvez activer plus d'options d'opérations dans vos <a href=\\\"/preferences\\\">paramètres</a>.\",\"destination_account\":\"Compte de destination\",\"add_another_split\":\"Ajouter une autre fraction\",\"submission\":\"Soumission\",\"create_another\":\"Après enregistrement, revenir ici pour en créer un nouveau.\",\"reset_after\":\"Réinitialiser le formulaire après soumission\",\"submit\":\"Soumettre\",\"amount\":\"Montant\",\"date\":\"Date\",\"tags\":\"Tags\",\"no_budget\":\"(pas de budget)\",\"category\":\"Catégorie\",\"attachments\":\"Pièces jointes\",\"notes\":\"Notes\",\"update_transaction\":\"Mettre à jour l'opération\",\"after_update_create_another\":\"Après la mise à jour, revenir ici pour continuer l'édition.\",\"store_as_new\":\"Enregistrer comme une nouvelle opération au lieu de mettre à jour.\",\"split_title_help\":\"Si vous créez une opération ventilée, il doit y avoir une description globale pour chaque fractions de l'opération.\",\"none_in_select_list\":\"(aucun)\",\"no_piggy_bank\":\"(aucune tirelire)\",\"description\":\"Description\",\"split_transaction_title_help\":\"Si vous créez une opération ventilée, il doit y avoir une description globale pour chaque fraction de l'opération.\",\"destination_account_reconciliation\":\"Vous ne pouvez pas modifier le compte de destination d'une opération de rapprochement.\",\"source_account_reconciliation\":\"Vous ne pouvez pas modifier le compte source d'une opération de rapprochement.\",\"budget\":\"Budget\",\"you_create_withdrawal\":\"Vous saisissez une dépense.\",\"you_create_transfer\":\"Vous saisissez un transfert.\",\"you_create_deposit\":\"Vous saisissez un dépôt.\"},\"form\":{\"interest_date\":\"Date de valeur (intérêts)\",\"book_date\":\"Date de réservation\",\"process_date\":\"Date de traitement\",\"due_date\":\"Échéance\",\"foreign_amount\":\"Montant en devise étrangère\",\"payment_date\":\"Date de paiement\",\"invoice_date\":\"Date de facturation\",\"internal_reference\":\"Référence interne\"},\"config\":{\"html_language\":\"fr\"},\"profile\":{\"oauth_clients\":\"Clients Oauth FR\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/hu.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/hu.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Mi a helyzet?\",\"flash_error\":\"Hiba!\",\"flash_success\":\"Siker!\",\"close\":\"Bezárás\",\"split_transaction_title\":\"Felosztott tranzakció leírása\",\"errors_submission\":\"Hiba történt a beküldés során. Kérem, javítsa az alábbi hibákat.\",\"split\":\"Felosztás\",\"transaction_journal_information\":\"Tranzakciós információk\",\"no_budget_pointer\":\"Úgy tűnik, még nincsenek költségkeretek. Költségkereteket a <a href=\\\"/budgets\\\">költségkeretek</a> oldalon lehet létrehozni. A költségkeretek segítenek nyomon követni a költségeket.\",\"source_account\":\"Forrás számla\",\"hidden_fields_preferences\":\"A <a href=\\\"/preferences\\\">beállításokban</a> több tranzakciós beállítási lehetőség is megadható.\",\"destination_account\":\"Célszámla\",\"add_another_split\":\"Másik felosztás hozzáadása\",\"submission\":\"Feliratkozás\",\"create_another\":\"A tárolás után térjen vissza ide új létrehozásához.\",\"reset_after\":\"Űrlap törlése a beküldés után\",\"submit\":\"Beküldés\",\"amount\":\"Összeg\",\"date\":\"Dátum\",\"tags\":\"Címkék\",\"no_budget\":\"(nincs költségkeret)\",\"category\":\"Kategória\",\"attachments\":\"Mellékletek\",\"notes\":\"Megjegyzések\",\"update_transaction\":\"Tranzakció frissítése\",\"after_update_create_another\":\"A frissítés után térjen vissza ide a szerkesztés folytatásához.\",\"store_as_new\":\"Tárolás új tranzakcióként frissítés helyett.\",\"split_title_help\":\"Felosztott tranzakció létrehozásakor meg kell adni egy globális leírást a tranzakció összes felosztása részére.\",\"none_in_select_list\":\"(nincs)\",\"no_piggy_bank\":\"(nincs malacpersely)\",\"description\":\"Leírás\",\"split_transaction_title_help\":\"Felosztott tranzakció létrehozásakor meg kell adni egy globális leírást a tranzakció összes felosztása részére.\",\"destination_account_reconciliation\":\"Nem lehet szerkeszteni egy egyeztetett tranzakció célszámláját.\",\"source_account_reconciliation\":\"Nem lehet szerkeszteni egy egyeztetett tranzakció forrásszámláját.\",\"budget\":\"Költségkeret\",\"you_create_withdrawal\":\"Egy költség létrehozása.\",\"you_create_transfer\":\"Egy átutalás létrehozása.\",\"you_create_deposit\":\"Egy bevétel létrehozása.\"},\"form\":{\"interest_date\":\"Kamatfizetési időpont\",\"book_date\":\"Könyvelés dátuma\",\"process_date\":\"Feldolgozás dátuma\",\"due_date\":\"Lejárati időpont\",\"foreign_amount\":\"Külföldi összeg\",\"payment_date\":\"Fizetés dátuma\",\"invoice_date\":\"Számla dátuma\",\"internal_reference\":\"Belső hivatkozás\"},\"config\":{\"html_language\":\"hu\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/id.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/id.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"What's playing?\",\"flash_error\":\"Kesalahan!\",\"flash_success\":\"Keberhasilan!\",\"close\":\"Dekat\",\"split_transaction_title\":\"Description of the split transaction\",\"errors_submission\":\"There was something wrong with your submission. Please check out the errors below.\",\"split\":\"Pisah\",\"transaction_journal_information\":\"Informasi transaksi\",\"no_budget_pointer\":\"You seem to have no budgets yet. You should create some on the <a href=\\\"/budgets\\\">budgets</a>-page. Budgets can help you keep track of expenses.\",\"source_account\":\"Source account\",\"hidden_fields_preferences\":\"You can enable more transaction options in your <a href=\\\"/preferences\\\">settings</a>.\",\"destination_account\":\"Destination account\",\"add_another_split\":\"Tambahkan perpecahan lagi\",\"submission\":\"Submission\",\"create_another\":\"After storing, return here to create another one.\",\"reset_after\":\"Reset form after submission\",\"submit\":\"Menyerahkan\",\"amount\":\"Jumlah\",\"date\":\"Tanggal\",\"tags\":\"Tag\",\"no_budget\":\"(no budget)\",\"category\":\"Kategori\",\"attachments\":\"Lampiran\",\"notes\":\"Notes\",\"update_transaction\":\"Update transaction\",\"after_update_create_another\":\"After updating, return here to continue editing.\",\"store_as_new\":\"Store as a new transaction instead of updating.\",\"split_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"none_in_select_list\":\"(none)\",\"no_piggy_bank\":\"(no piggy bank)\",\"description\":\"Deskripsi\",\"split_transaction_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"destination_account_reconciliation\":\"You can't edit the destination account of a reconciliation transaction.\",\"source_account_reconciliation\":\"You can't edit the source account of a reconciliation transaction.\",\"budget\":\"Anggaran\",\"you_create_withdrawal\":\"You're creating a withdrawal.\",\"you_create_transfer\":\"You're creating a transfer.\",\"you_create_deposit\":\"You're creating a deposit.\"},\"form\":{\"interest_date\":\"Tanggal bunga\",\"book_date\":\"Tanggal buku\",\"process_date\":\"Tanggal pemrosesan\",\"due_date\":\"Batas tanggal terakhir\",\"foreign_amount\":\"Foreign amount\",\"payment_date\":\"Tanggal pembayaran\",\"invoice_date\":\"Tanggal faktur\",\"internal_reference\":\"Referensi internal\"},\"config\":{\"html_language\":\"id\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/it.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/it.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"La tua situazione finanziaria\",\"flash_error\":\"Errore!\",\"flash_success\":\"Successo!\",\"close\":\"Chiudi\",\"split_transaction_title\":\"Descrizione della transazione suddivisa\",\"errors_submission\":\"Errore durante l'invio. Controlla gli errori segnalati qui sotto.\",\"split\":\"Dividi\",\"transaction_journal_information\":\"Informazioni transazione\",\"no_budget_pointer\":\"Sembra che tu non abbia ancora dei budget. Dovresti crearne alcuni nella pagina dei <a href=\\\"/budgets\\\">budget</a>. I budget possono aiutarti a tenere traccia delle spese.\",\"source_account\":\"Conto di origine\",\"hidden_fields_preferences\":\"Puoi abilitare maggiori opzioni per le transazioni nelle tue <a href=\\\"/preferences\\\">impostazioni</a>.\",\"destination_account\":\"Conto destinazione\",\"add_another_split\":\"Aggiungi un'altra divisione\",\"submission\":\"Invio\",\"create_another\":\"Dopo il salvataggio, torna qui per crearne un'altra.\",\"reset_after\":\"Resetta il modulo dopo l'invio\",\"submit\":\"Invia\",\"amount\":\"Importo\",\"date\":\"Data\",\"tags\":\"Etichette\",\"no_budget\":\"(nessun budget)\",\"category\":\"Categoria\",\"attachments\":\"Allegati\",\"notes\":\"Note\",\"update_transaction\":\"Aggiorna transazione\",\"after_update_create_another\":\"Dopo l'aggiornamento, torna qui per continuare la modifica.\",\"store_as_new\":\"Salva come nuova transazione invece di aggiornarla.\",\"split_title_help\":\"Se crei una transazione suddivisa è necessario che ci sia una descrizione globale per tutte le suddivisioni della transazione.\",\"none_in_select_list\":\"(nessuna)\",\"no_piggy_bank\":\"(nessun salvadanaio)\",\"description\":\"Descrizione\",\"split_transaction_title_help\":\"Se crei una transazione suddivisa, è necessario che ci sia una descrizione globale per tutte le suddivisioni della transazione.\",\"destination_account_reconciliation\":\"Non è possibile modificare il conto di destinazione di una transazione di riconciliazione.\",\"source_account_reconciliation\":\"Non puoi modificare il conto di origine di una transazione di riconciliazione.\",\"budget\":\"Budget\",\"you_create_withdrawal\":\"Stai creando un prelievo.\",\"you_create_transfer\":\"Stai creando un trasferimento.\",\"you_create_deposit\":\"Stai creando un deposito.\"},\"form\":{\"interest_date\":\"Data interesse\",\"book_date\":\"Data contabile\",\"process_date\":\"Data elaborazione\",\"due_date\":\"Data scadenza\",\"foreign_amount\":\"Importo estero\",\"payment_date\":\"Data pagamento\",\"invoice_date\":\"Data fatturazione\",\"internal_reference\":\"Riferimento interno\"},\"config\":{\"html_language\":\"it\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/nl.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/nl.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Hoe staat het er voor?\",\"flash_error\":\"Fout!\",\"flash_success\":\"Gelukt!\",\"close\":\"Sluiten\",\"split_transaction_title\":\"Beschrijving van de gesplitste transactie\",\"errors_submission\":\"Er ging iets mis. Check de errors.\",\"split\":\"Splitsen\",\"transaction_journal_information\":\"Transactieinformatie\",\"no_budget_pointer\":\"Je hebt nog geen budgetten. Maak er een aantal op de <a href=\\\"/budgets\\\">budgetten</a>-pagina. Met budgetten kan je je uitgaven beter bijhouden.\",\"source_account\":\"Bronrekening\",\"hidden_fields_preferences\":\"Je kan meer transactieopties inschakelen in je <a href=\\\"/preferences\\\">instellingen</a>.\",\"destination_account\":\"Doelrekening\",\"add_another_split\":\"Voeg een split toe\",\"submission\":\"Indienen\",\"create_another\":\"Terug naar deze pagina voor een nieuwe transactie.\",\"reset_after\":\"Reset formulier na opslaan\",\"submit\":\"Invoeren\",\"amount\":\"Bedrag\",\"date\":\"Datum\",\"tags\":\"Tags\",\"no_budget\":\"(geen budget)\",\"category\":\"Categorie\",\"attachments\":\"Bijlagen\",\"notes\":\"Notities\",\"update_transaction\":\"Update transactie\",\"after_update_create_another\":\"Na het opslaan terug om door te gaan met wijzigen.\",\"store_as_new\":\"Opslaan als nieuwe transactie ipv de huidige bij te werken.\",\"split_title_help\":\"Als je een gesplitste transactie maakt, moet er een algemene beschrijving zijn voor alle splitsingen van de transactie.\",\"none_in_select_list\":\"(geen)\",\"no_piggy_bank\":\"(geen spaarpotje)\",\"description\":\"Omschrijving\",\"split_transaction_title_help\":\"Als je een gesplitste transactie maakt, moet er een algemene beschrijving zijn voor alle splitsingen van de transactie.\",\"destination_account_reconciliation\":\"Je kan de doelrekening van een afstemming niet wijzigen.\",\"source_account_reconciliation\":\"Je kan de bronrekening van een afstemming niet wijzigen.\",\"budget\":\"Budget\",\"you_create_withdrawal\":\"Je maakt een uitgave.\",\"you_create_transfer\":\"Je maakt een overschrijving.\",\"you_create_deposit\":\"Je maakt inkomsten.\"},\"form\":{\"interest_date\":\"Rentedatum\",\"book_date\":\"Boekdatum\",\"process_date\":\"Verwerkingsdatum\",\"due_date\":\"Vervaldatum\",\"foreign_amount\":\"Bedrag in vreemde valuta\",\"payment_date\":\"Betalingsdatum\",\"invoice_date\":\"Factuurdatum\",\"internal_reference\":\"Interne verwijzing\"},\"config\":{\"html_language\":\"nl\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/no.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/no.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Hvordan går det?\",\"flash_error\":\"Feil!\",\"flash_success\":\"Suksess!\",\"close\":\"Lukk\",\"split_transaction_title\":\"Description of the split transaction\",\"split\":\"Del opp\",\"transaction_journal_information\":\"Transaksjonsinformasjon\",\"source_account\":\"Source account\",\"destination_account\":\"Destination account\",\"add_another_split\":\"Legg til en oppdeling til\",\"submit\":\"Send inn\",\"amount\":\"Beløp\",\"no_budget\":\"(ingen budsjett)\",\"category\":\"Kategori\",\"attachments\":\"Vedlegg\",\"notes\":\"Notater\"},\"form\":{\"interest_date\":\"Rentedato\",\"book_date\":\"Bokføringsdato\",\"process_date\":\"Prosesseringsdato\",\"due_date\":\"Forfallsdato\",\"payment_date\":\"Betalingsdato\",\"invoice_date\":\"Fakturadato\",\"internal_reference\":\"Intern referanse\"},\"config\":{\"html_language\":\"no\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/pl.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/pl.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Co jest grane?\",\"flash_error\":\"Błąd!\",\"flash_success\":\"Sukces!\",\"close\":\"Zamknij\",\"split_transaction_title\":\"Opis podzielonej transakcji\",\"errors_submission\":\"Coś poszło nie tak w czasie zapisu. Proszę sprawdź błędy poniżej.\",\"split\":\"Podziel\",\"transaction_journal_information\":\"Informacje o transakcji\",\"no_budget_pointer\":\"Wygląda na to że nie masz jeszcze budżetów. Powinieneś utworzyć kilka na stronie <a href=\\\"/budgets\\\">budżety</a>. Budżety mogą Ci pomóc śledzić wydatki.\",\"source_account\":\"Konto źródłowe\",\"hidden_fields_preferences\":\"Możesz włączyć więcej opcji transakcji w swoich <a href=\\\"/preferences\\\">ustawieniach</a>.\",\"destination_account\":\"Konto docelowe\",\"add_another_split\":\"Dodaj kolejny podział\",\"submission\":\"Zapisz\",\"create_another\":\"Po zapisaniu wróć tutaj, aby utworzyć kolejny.\",\"reset_after\":\"Wyczyść formularz po zapisaniu\",\"submit\":\"Prześlij\",\"amount\":\"Kwota\",\"date\":\"Data\",\"tags\":\"Tagi\",\"no_budget\":\"(brak budżetu)\",\"category\":\"Kategoria\",\"attachments\":\"Załączniki\",\"notes\":\"Notatki\",\"update_transaction\":\"Zaktualizuj transakcję\",\"after_update_create_another\":\"Po aktualizacji wróć tutaj, aby kontynuować edycję.\",\"store_as_new\":\"Zapisz jako nową zamiast aktualizować.\",\"split_title_help\":\"Podzielone transakcje muszą posiadać globalny opis.\",\"none_in_select_list\":\"(żadne)\",\"no_piggy_bank\":\"(brak skarbonki)\",\"description\":\"Opis\",\"split_transaction_title_help\":\"Jeśli tworzysz podzieloną transakcję, musi ona posiadać globalny opis dla wszystkich podziałów w transakcji.\",\"destination_account_reconciliation\":\"Nie możesz edytować konta docelowego transakcji uzgadniania.\",\"source_account_reconciliation\":\"Nie możesz edytować konta źródłowego transakcji uzgadniania.\",\"budget\":\"Budżet\",\"you_create_withdrawal\":\"Tworzysz wydatek.\",\"you_create_transfer\":\"Tworzysz przelew.\",\"you_create_deposit\":\"Tworzysz wpłatę.\"},\"form\":{\"interest_date\":\"Data odsetek\",\"book_date\":\"Data księgowania\",\"process_date\":\"Data przetworzenia\",\"due_date\":\"Termin realizacji\",\"foreign_amount\":\"Kwota zagraniczna\",\"payment_date\":\"Data płatności\",\"invoice_date\":\"Data faktury\",\"internal_reference\":\"Wewnętrzny numer\"},\"config\":{\"html_language\":\"pl\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/pt-br.json":
/*!************************************************!*\
  !*** ./resources/assets/js/locales/pt-br.json ***!
  \************************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"O que está acontecendo?\",\"flash_error\":\"Erro!\",\"flash_success\":\"Sucesso!\",\"close\":\"Fechar\",\"split_transaction_title\":\"Descrição da transação dividida\",\"errors_submission\":\"There was something wrong with your submission. Please check out the errors below.\",\"split\":\"Dividir\",\"transaction_journal_information\":\"Informação da transação\",\"no_budget_pointer\":\"Parece que você ainda não tem orçamentos. Você deve criar alguns na página de <a href=\\\"/budgets\\\">orçamentos</a>. Orçamentos podem ajudá-lo a manter o controle das despesas.\",\"source_account\":\"Conta origem\",\"hidden_fields_preferences\":\"You can enable more transaction options in your <a href=\\\"/preferences\\\">settings</a>.\",\"destination_account\":\"Conta destino\",\"add_another_split\":\"Adicionar outra divisão\",\"submission\":\"Submission\",\"create_another\":\"After storing, return here to create another one.\",\"reset_after\":\"Reset form after submission\",\"submit\":\"Enviar\",\"amount\":\"Valor\",\"date\":\"Data\",\"tags\":\"Tags\",\"no_budget\":\"(sem orçamento)\",\"category\":\"Categoria\",\"attachments\":\"Anexos\",\"notes\":\"Notas\",\"update_transaction\":\"Update transaction\",\"after_update_create_another\":\"After updating, return here to continue editing.\",\"store_as_new\":\"Store as a new transaction instead of updating.\",\"split_title_help\":\"Se você criar uma transação dividida, é necessário haver uma descrição global para todas as partes da transação.\",\"none_in_select_list\":\"(nenhum)\",\"no_piggy_bank\":\"(nenhum cofrinho)\",\"description\":\"Descrição\",\"split_transaction_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"destination_account_reconciliation\":\"Você não pode editar a conta de origem de uma transação de reconciliação.\",\"source_account_reconciliation\":\"Você não pode editar a conta de origem de uma transação de reconciliação.\",\"budget\":\"Orçamento\",\"you_create_withdrawal\":\"You're creating a withdrawal.\",\"you_create_transfer\":\"You're creating a transfer.\",\"you_create_deposit\":\"You're creating a deposit.\"},\"form\":{\"interest_date\":\"Data de interesse\",\"book_date\":\"Data reserva\",\"process_date\":\"Data de processamento\",\"due_date\":\"Data de vencimento\",\"foreign_amount\":\"Montante em moeda estrangeira\",\"payment_date\":\"Data de pagamento\",\"invoice_date\":\"Data da Fatura\",\"internal_reference\":\"Referência interna\"},\"config\":{\"html_language\":\"pt-br\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/ro.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/ro.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Ce se redă?\",\"flash_error\":\"Eroare!\",\"flash_success\":\"Succes!\",\"close\":\"Închide\",\"split_transaction_title\":\"Descrierea tranzacției divizate\",\"errors_submission\":\"A fost ceva în neregulă cu transmiterea dvs. Vă rugăm să consultați erorile de mai jos.\",\"split\":\"Împarte\",\"transaction_journal_information\":\"Informații despre tranzacții\",\"no_budget_pointer\":\"You seem to have no budgets yet. You should create some on the <a href=\\\"/budgets\\\">budgets</a>-page. Budgets can help you keep track of expenses.\",\"source_account\":\"Contul sursă\",\"hidden_fields_preferences\":\"You can enable more transaction options in your <a href=\\\"/preferences\\\">settings</a>.\",\"destination_account\":\"Contul de destinație\",\"add_another_split\":\"Adăugați o divizare\",\"submission\":\"Transmitere\",\"create_another\":\"După stocare, reveniți aici pentru a crea alta.\",\"reset_after\":\"Resetați formularul după trimitere\",\"submit\":\"Trimite\",\"amount\":\"Sumă\",\"date\":\"Dată\",\"tags\":\"Etichete\",\"no_budget\":\"(nici un buget)\",\"category\":\"Categorie\",\"attachments\":\"Atașamente\",\"notes\":\"Notițe\",\"update_transaction\":\"Actualizați tranzacția\",\"after_update_create_another\":\"După actualizare, reveniți aici pentru a continua editarea.\",\"store_as_new\":\"Stocați ca o tranzacție nouă în loc să actualizați.\",\"split_title_help\":\"Dacă creați o tranzacție divizată, trebuie să existe o descriere globală pentru toate diviziunile tranzacției.\",\"none_in_select_list\":\"(nici unul)\",\"no_piggy_bank\":\"(nicio pușculiță)\",\"description\":\"Descriere\",\"split_transaction_title_help\":\"Dacă creați o tranzacție divizată, trebuie să existe o descriere globală pentru toate diviziunile tranzacției.\",\"destination_account_reconciliation\":\"Nu puteți edita contul de destinație al unei tranzacții de reconciliere.\",\"source_account_reconciliation\":\"Nu puteți edita contul sursă al unei tranzacții de reconciliere.\",\"budget\":\"Buget\",\"you_create_withdrawal\":\"You're creating a withdrawal.\",\"you_create_transfer\":\"You're creating a transfer.\",\"you_create_deposit\":\"You're creating a deposit.\"},\"form\":{\"interest_date\":\"Data de interes\",\"book_date\":\"Rezervă dată\",\"process_date\":\"Data procesării\",\"due_date\":\"Data scadentă\",\"foreign_amount\":\"Sumă străină\",\"payment_date\":\"Data de plată\",\"invoice_date\":\"Data facturii\",\"internal_reference\":\"Referință internă\"},\"config\":{\"html_language\":\"ro\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/ru.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/ru.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Что происходит с моими финансами?\",\"flash_error\":\"Ошибка!\",\"flash_success\":\"Успешно!\",\"close\":\"Закрыть\",\"split_transaction_title\":\"Описание разделённой транзакции\",\"errors_submission\":\"При отправке произошла ошибка. Пожалуйста, проверьте ошибки ниже.\",\"split\":\"Разделить\",\"transaction_journal_information\":\"Информация о транзакции\",\"no_budget_pointer\":\"Похоже, у вас пока нет бюджетов. Вы должны создать их в разделе <a href=\\\"/budgets\\\">Бюджеты</a>. Бюджеты могут помочь вам отслеживать расходы.\",\"source_account\":\"Счёт-источник\",\"hidden_fields_preferences\":\"You can enable more transaction options in your <a href=\\\"/preferences\\\">settings</a>.\",\"destination_account\":\"Счёт назначения\",\"add_another_split\":\"Добавить новую часть\",\"submission\":\"Отправить\",\"create_another\":\"После сохранения вернуться сюда и создать ещё одну аналогичную запись.\",\"reset_after\":\"Сбросить форму после отправки\",\"submit\":\"Подтвердить\",\"amount\":\"Сумма\",\"date\":\"Дата\",\"tags\":\"Метки\",\"no_budget\":\"(вне бюджета)\",\"category\":\"Категория\",\"attachments\":\"Вложения\",\"notes\":\"Заметки\",\"update_transaction\":\"Обновить транзакцию\",\"after_update_create_another\":\"После обновления вернитесь сюда, чтобы продолжить редактирование.\",\"store_as_new\":\"Сохранить как новую транзакцию вместо обновления.\",\"split_title_help\":\"Если вы создаёте разделённую транзакцию, то должны указать общее описание дле всех её составляющих.\",\"none_in_select_list\":\"(нет)\",\"no_piggy_bank\":\"(нет копилки)\",\"description\":\"Описание\",\"split_transaction_title_help\":\"Если вы создаёте разделённую транзакцию, то должны указать общее описание для всех её составляющих.\",\"destination_account_reconciliation\":\"You can't edit the destination account of a reconciliation transaction.\",\"source_account_reconciliation\":\"Вы не можете редактировать исходный аккаунт сверки.\",\"budget\":\"Бюджет\",\"you_create_withdrawal\":\"You're creating a withdrawal.\",\"you_create_transfer\":\"You're creating a transfer.\",\"you_create_deposit\":\"You're creating a deposit.\"},\"form\":{\"interest_date\":\"Дата выплаты\",\"book_date\":\"Дата бронирования\",\"process_date\":\"Дата обработки\",\"due_date\":\"Срок\",\"foreign_amount\":\"Сумма в иностранной валюте\",\"payment_date\":\"Дата платежа\",\"invoice_date\":\"Дата выставления счёта\",\"internal_reference\":\"Внутренняя ссылка\"},\"config\":{\"html_language\":\"ru\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/sv.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/sv.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Vad spelas?\",\"flash_error\":\"Fel!\",\"flash_success\":\"Slutförd!\",\"close\":\"Stäng\",\"split_transaction_title\":\"Description of the split transaction\",\"errors_submission\":\"Något fel uppstod med inskickningen. Vänligen kontrollera felen nedan.\",\"split\":\"Dela\",\"transaction_journal_information\":\"Transaktionsinformation\",\"no_budget_pointer\":\"You seem to have no budgets yet. You should create some on the <a href=\\\"/budgets\\\">budgets</a>-page. Budgets can help you keep track of expenses.\",\"source_account\":\"Från konto\",\"hidden_fields_preferences\":\"You can enable more transaction options in your <a href=\\\"/preferences\\\">settings</a>.\",\"destination_account\":\"Till konto\",\"add_another_split\":\"Lägga till en annan delning\",\"submission\":\"Inskickning\",\"create_another\":\"Efter sparat, återkom hit för att skapa ytterligare en.\",\"reset_after\":\"Återställ formulär efter inskickat\",\"submit\":\"Skicka\",\"amount\":\"Belopp\",\"date\":\"Datum\",\"tags\":\"Etiketter\",\"no_budget\":\"(ingen budget)\",\"category\":\"Kategori\",\"attachments\":\"Bilagor\",\"notes\":\"Noteringar\",\"update_transaction\":\"Uppdatera transaktion\",\"after_update_create_another\":\"Efter uppdaterat, återkom hit för att fortsätta redigera.\",\"store_as_new\":\"Spara en ny transaktion istället för att uppdatera.\",\"split_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"none_in_select_list\":\"(Ingen)\",\"no_piggy_bank\":\"(ingen spargris)\",\"description\":\"Beskrivning\",\"split_transaction_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"destination_account_reconciliation\":\"Du kan inte redigera destinationskontot för en avstämningstransaktion.\",\"source_account_reconciliation\":\"Du kan inte redigera källkontot för en avstämningstransaktion.\",\"budget\":\"Budget\",\"you_create_withdrawal\":\"You're creating a withdrawal.\",\"you_create_transfer\":\"You're creating a transfer.\",\"you_create_deposit\":\"You're creating a deposit.\"},\"form\":{\"interest_date\":\"Räntedatum\",\"book_date\":\"Bokföringsdatum\",\"process_date\":\"Behandlingsdatum\",\"due_date\":\"Förfallodatum\",\"foreign_amount\":\"Utländskt belopp\",\"payment_date\":\"Betalningsdatum\",\"invoice_date\":\"Fakturadatum\",\"internal_reference\":\"Intern referens\"},\"config\":{\"html_language\":\"sv\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/vi.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/vi.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"Chào mừng trở lại\",\"flash_error\":\"Lỗi!\",\"flash_success\":\"Thành công!\",\"close\":\"Đóng\",\"split_transaction_title\":\"Mô tả giao dịch tách\",\"errors_submission\":\"Có gì đó sai. Vui lòng kiểm tra các lỗi dưới đây.\",\"split\":\"Chia ra\",\"transaction_journal_information\":\"Thông tin giao dịch\",\"no_budget_pointer\":\"Bạn dường như chưa có ngân sách. Bạn nên tạo một cái trên <a href=\\\":link\\\">budgets</a>-page. Ngân sách có thể giúp bạn theo dõi chi phí.\",\"source_account\":\"Nguồn tài khoản\",\"hidden_fields_preferences\":\"Bạn có thể kích hoạt thêm tùy chọn giao dịch trong <a href=\\\":link\\\">settings</a>.\",\"destination_account\":\"Tài khoản đích\",\"add_another_split\":\"Thêm một phân chia khác\",\"submission\":\"Gửi\",\"create_another\":\"Sau khi lưu trữ, quay trở lại đây để tạo một cái khác.\",\"reset_after\":\"Đặt lại mẫu sau khi gửi\",\"submit\":\"Gửi\",\"amount\":\"Số tiền\",\"date\":\"Ngày\",\"tags\":\"Thẻ\",\"no_budget\":\"(không có ngân sách)\",\"category\":\"Dan hmucj\",\"attachments\":\"Tệp đính kèm\",\"notes\":\"Ghi chú\",\"update_transaction\":\"Cập nhật giao dịch\",\"after_update_create_another\":\"Sau khi cập nhật, quay lại đây để tiếp tục chỉnh sửa.\",\"store_as_new\":\"Lưu trữ như một giao dịch mới thay vì cập nhật.\",\"split_title_help\":\"Nếu bạn tạo một giao dịch phân tách, phải có một mô tả toàn cầu cho tất cả các phân chia của giao dịch.\",\"none_in_select_list\":\"(none)\",\"no_piggy_bank\":\"(no piggy bank)\",\"description\":\"Sự miêu tả\",\"split_transaction_title_help\":\"Nếu bạn tạo một giao dịch phân tách, phải có một mô tả toàn cầu cho tất cả các phân chia của giao dịch.\",\"destination_account_reconciliation\":\"Bạn không thể chỉnh sửa tài khoản đích của giao dịch đối chiếu.\",\"source_account_reconciliation\":\"Bạn không thể chỉnh sửa tài khoản nguồn của giao dịch đối chiếu.\",\"budget\":\"Ngân sách\",\"you_create_withdrawal\":\"Bạn đang tạo một <strong>rút tiền</strong>.\",\"you_create_transfer\":\"Bạn đang tạo một <strong>chuyển khoản</strong>.\",\"you_create_deposit\":\"Bạn đang tạo một <strong>tiền gửi</strong>.\"},\"form\":{\"interest_date\":\"Ngày lãi\",\"book_date\":\"Ngày đặt sách\",\"process_date\":\"Ngày xử lý\",\"due_date\":\"Ngày đáo hạn\",\"foreign_amount\":\"Ngoại tệ\",\"payment_date\":\"Ngày thanh toán\",\"invoice_date\":\"Ngày hóa đơn\",\"internal_reference\":\"Tài liệu tham khảo nội bộ\"},\"config\":{\"html_language\":\"vi\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/zh-cn.json":
/*!************************************************!*\
  !*** ./resources/assets/js/locales/zh-cn.json ***!
  \************************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"今天理财了吗？\",\"flash_error\":\"错误！\",\"flash_success\":\"成功！\",\"close\":\"关闭\",\"split_transaction_title\":\"拆分交易的描述\",\"errors_submission\":\"您的提交有误，请查看下面输出的错误信息。\",\"split\":\"分割\",\"transaction_journal_information\":\"交易资讯\",\"no_budget_pointer\":\"您似乎还没有任何预算。您应该在 <a href=\\\"/budgets\\\">预算</a>页面上创建他们。预算可以帮助您跟踪费用。\",\"source_account\":\"来源帐户\",\"hidden_fields_preferences\":\"您可以在 <a href=\\\"/preferences\\\">设置</a>中启用更多的交易选项。\",\"destination_account\":\"目标帐户\",\"add_another_split\":\"增加拆分\",\"submission\":\"提交\",\"create_another\":\"保存后，返回此页面创建另一笔记录。\",\"reset_after\":\"提交后重置表单\",\"submit\":\"提交\",\"amount\":\"金额\",\"date\":\"日期\",\"tags\":\"标签\",\"no_budget\":\"(无预算)\",\"category\":\"分类\",\"attachments\":\"附加档案\",\"notes\":\"注释\",\"update_transaction\":\"更新交易\",\"after_update_create_another\":\"更新后，返回此页面继续编辑。\",\"store_as_new\":\"保存为新交易而不是更新此交易。\",\"split_title_help\":\"如果您创建一个拆分交易，必须有一个全局的交易描述。\",\"none_in_select_list\":\"（空）\",\"no_piggy_bank\":\"（无存钱罐）\",\"description\":\"描述\",\"split_transaction_title_help\":\"如果您创建了一个分割交易，交易的所有分割项都必须有全局描述。\",\"destination_account_reconciliation\":\"您不能编辑对账交易的目标账户\",\"source_account_reconciliation\":\"您不能编辑对账交易的源账户\",\"budget\":\"预算\",\"you_create_withdrawal\":\"您正在创建一个提款\",\"you_create_transfer\":\"您正在创建一个转账\",\"you_create_deposit\":\"您正在创建一个存款\"},\"form\":{\"interest_date\":\"利率日期\",\"book_date\":\"登记日期\",\"process_date\":\"处理日期\",\"due_date\":\"到期日\",\"foreign_amount\":\"外币金额\",\"payment_date\":\"付款日期\",\"invoice_date\":\"发票日期\",\"internal_reference\":\"内部参考\"},\"config\":{\"html_language\":\"zh-cn\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/zh-tw.json":
/*!************************************************!*\
  !*** ./resources/assets/js/locales/zh-tw.json ***!
  \************************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"What's playing?\",\"flash_error\":\"錯誤！\",\"flash_success\":\"成功！\",\"close\":\"關閉\",\"split_transaction_title\":\"拆分交易的描述\",\"errors_submission\":\"There was something wrong with your submission. Please check out the errors below.\",\"split\":\"分割\",\"transaction_journal_information\":\"交易資訊\",\"no_budget_pointer\":\"You seem to have no budgets yet. You should create some on the <a href=\\\"/budgets\\\">budgets</a>-page. Budgets can help you keep track of expenses.\",\"source_account\":\"Source account\",\"hidden_fields_preferences\":\"You can enable more transaction options in your <a href=\\\"/preferences\\\">settings</a>.\",\"destination_account\":\"Destination account\",\"add_another_split\":\"增加拆分\",\"submission\":\"Submission\",\"create_another\":\"After storing, return here to create another one.\",\"reset_after\":\"Reset form after submission\",\"submit\":\"送出\",\"amount\":\"金額\",\"date\":\"日期\",\"tags\":\"標籤\",\"no_budget\":\"(無預算)\",\"category\":\"分類\",\"attachments\":\"附加檔案\",\"notes\":\"備註\",\"update_transaction\":\"Update transaction\",\"after_update_create_another\":\"After updating, return here to continue editing.\",\"store_as_new\":\"Store as a new transaction instead of updating.\",\"split_title_help\":\"若您建立一筆拆分交易，須有一個有關交易所有拆分的整體描述。\",\"none_in_select_list\":\"(空)\",\"no_piggy_bank\":\"(no piggy bank)\",\"description\":\"描述\",\"split_transaction_title_help\":\"If you create a split transaction, there must be a global description for all splits of the transaction.\",\"destination_account_reconciliation\":\"You can't edit the destination account of a reconciliation transaction.\",\"source_account_reconciliation\":\"You can't edit the source account of a reconciliation transaction.\",\"budget\":\"預算\",\"you_create_withdrawal\":\"You're creating a withdrawal.\",\"you_create_transfer\":\"You're creating a transfer.\",\"you_create_deposit\":\"You're creating a deposit.\"},\"form\":{\"interest_date\":\"利率日期\",\"book_date\":\"登記日期\",\"process_date\":\"處理日期\",\"due_date\":\"到期日\",\"foreign_amount\":\"外幣金額\",\"payment_date\":\"付款日期\",\"invoice_date\":\"發票日期\",\"internal_reference\":\"內部參考\"},\"config\":{\"html_language\":\"zh-tw\"}}");

/***/ }),

/***/ "./resources/assets/js/locales/zh.json":
/*!*********************************************!*\
  !*** ./resources/assets/js/locales/zh.json ***!
  \*********************************************/
/*! exports provided: firefly, form, config, default */
/***/ (function(module) {

module.exports = JSON.parse("{\"firefly\":{\"welcome_back\":\"吃饱没？\",\"flash_error\":\"错误！\",\"flash_success\":\"成功！\",\"close\":\"关闭\",\"split_transaction_title\":\"拆分交易的描述\",\"split\":\"分割\",\"transaction_journal_information\":\"交易资讯\",\"source_account\":\"来源帐户\",\"destination_account\":\"目标帐户\",\"add_another_split\":\"增加拆分\",\"submit\":\"送出\",\"amount\":\"金额\",\"no_budget\":\"(无预算)\",\"category\":\"分类\",\"attachments\":\"附加档案\",\"notes\":\"注释\"},\"form\":{\"interest_date\":\"利率日期\",\"book_date\":\"登记日期\",\"process_date\":\"处理日期\",\"due_date\":\"到期日\",\"payment_date\":\"付款日期\",\"invoice_date\":\"发票日期\",\"internal_reference\":\"内部参考\"},\"config\":{\"html_language\":\"zh\"}}");

/***/ }),

/***/ "./resources/assets/js/profile.js":
/*!****************************************!*\
  !*** ./resources/assets/js/profile.js ***!
  \****************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _components_passport_Clients__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/passport/Clients */ "./resources/assets/js/components/passport/Clients.vue");
/* harmony import */ var _components_passport_AuthorizedClients__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/passport/AuthorizedClients */ "./resources/assets/js/components/passport/AuthorizedClients.vue");
/* harmony import */ var _components_passport_PersonalAccessTokens__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/passport/PersonalAccessTokens */ "./resources/assets/js/components/passport/PersonalAccessTokens.vue");
/* harmony import */ var _components_profile_ProfileOptions__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/profile/ProfileOptions */ "./resources/assets/js/components/profile/ProfileOptions.vue");
/*
 * profile.js
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */




/**
 * First we will load Axios via bootstrap.js
 * jquery and bootstrap-sass preloaded in app.js
 * vue, uiv and vuei18n are in app_vue.js
 */

__webpack_require__(/*! ./bootstrap */ "./resources/assets/js/bootstrap.js");

Vue.component('passport-clients', _components_passport_Clients__WEBPACK_IMPORTED_MODULE_0__["default"]);
Vue.component('passport-authorized-clients', _components_passport_AuthorizedClients__WEBPACK_IMPORTED_MODULE_1__["default"]);
Vue.component('passport-personal-access-tokens', _components_passport_PersonalAccessTokens__WEBPACK_IMPORTED_MODULE_2__["default"]);
Vue.component('profile-options', _components_profile_ProfileOptions__WEBPACK_IMPORTED_MODULE_3__["default"]);

var i18n = __webpack_require__(/*! ./i18n */ "./resources/assets/js/i18n.js");

var props = {};
new Vue({
  i18n: i18n,
  el: "#passport_clients",
  render: function render(createElement) {
    return createElement(_components_profile_ProfileOptions__WEBPACK_IMPORTED_MODULE_3__["default"], {
      props: props
    });
  }
});

/***/ }),

/***/ 4:
/*!**********************************************!*\
  !*** multi ./resources/assets/js/profile.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! C:\Users\Florian\github\firefly-iii\resources\assets\js\profile.js */"./resources/assets/js/profile.js");


/***/ })

/******/ });