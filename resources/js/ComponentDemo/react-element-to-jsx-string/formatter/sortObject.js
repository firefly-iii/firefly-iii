Object.defineProperty(exports, "__esModule", {
  value: true,
});

var _typeof =
  typeof Symbol === "function" && typeof Symbol.iterator === "symbol"
    ? function(obj) {
        return typeof obj;
      }
    : function(obj) {
        return obj &&
          typeof Symbol === "function" &&
          obj.constructor === Symbol &&
          obj !== Symbol.prototype
          ? "symbol"
          : typeof obj;
      };

exports.default = sortObject;
function sortObject(value) {
  // return non-object value as is
  if (
    value === null ||
    (typeof value === "undefined" ? "undefined" : _typeof(value)) !== "object"
  ) {
    return value;
  }

  // return date and regexp values as is
  if (value instanceof Date || value instanceof RegExp) {
    return value;
  }

  // make a copy of array with each item passed through sortObject()
  if (Array.isArray(value)) {
    return value.map(sortObject);
  }

  // make a copy of object with key sorted
  return Object.keys(value)
    .sort()
    .reduce(function(result, key) {
      // eslint-disable-next-line no-param-reassign
      result[key] = sortObject(value[key]);
      return result;
    }, {});
}
//# sourceMappingURL=sortObject.js.map
