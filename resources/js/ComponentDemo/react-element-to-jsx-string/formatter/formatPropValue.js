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

var _isPlainObject = require("is-plain-object");

var _isPlainObject2 = _interopRequireDefault(_isPlainObject);

var _react = require("react");

var _formatComplexDataStructure = require("./formatComplexDataStructure");

var _formatComplexDataStructure2 = _interopRequireDefault(
  _formatComplexDataStructure
);

var _formatTreeNode = require("./formatTreeNode");

var _formatTreeNode2 = _interopRequireDefault(_formatTreeNode);

var _parseReactElement = require("./../parser/parseReactElement");

var _parseReactElement2 = _interopRequireDefault(_parseReactElement);

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : { default: obj };
}

var noRefCheck = function noRefCheck() {};
var escape = function escape(s) {
  return s.replace(/"/g, "&quot;");
};

var defaultFunctionValue = function defaultFunctionValue(fn) {
  return fn;
};

var formatPropValue = function formatPropValue(
  propValue,
  inline,
  lvl,
  options
) {
  if (typeof propValue === "number") {
    return "{" + String(propValue) + "}";
  }

  if (typeof propValue === "string") {
    return '"' + escape(propValue) + '"';
  }

  // > "Symbols (new in ECMAScript 2015, not yet supported in Flow)"
  // @see: https://flow.org/en/docs/types/primitives/
  // $FlowFixMe: Flow does not support Symbol
  if (
    (typeof propValue === "undefined" ? "undefined" : _typeof(propValue)) ===
    "symbol"
  ) {
    var symbolDescription = propValue
      .valueOf()
      .toString()
      .replace(/Symbol\((.*)\)/, "$1");

    if (!symbolDescription) {
      return "{Symbol()}";
    }

    return "{Symbol('" + symbolDescription + "')}";
  }

  if (typeof propValue === "function") {
    var _options$functionValu = options.functionValue,
      functionValue =
        _options$functionValu === undefined
          ? defaultFunctionValue
          : _options$functionValu,
      showFunctions = options.showFunctions;

    if (!showFunctions && functionValue === defaultFunctionValue) {
      return "{" + functionValue(noRefCheck) + "}";
    }

    return "{" + functionValue(propValue) + "}";
  }

  if ((0, _react.isValidElement)(propValue)) {
    return (
      "{" +
      (0, _formatTreeNode2.default)(
        (0, _parseReactElement2.default)(propValue, options),
        true,
        lvl,
        options
      ) +
      "}"
    );
  }

  if (propValue instanceof Date) {
    return '{new Date("' + propValue.toISOString() + '")}';
  }

  if ((0, _isPlainObject2.default)(propValue) || Array.isArray(propValue)) {
    return (
      "{" +
      (0, _formatComplexDataStructure2.default)(
        propValue,
        inline,
        lvl,
        options
      ) +
      "}"
    );
  }

  return "{" + String(propValue) + "}";
};

exports.default = formatPropValue;
//# sourceMappingURL=formatPropValue.js.map
