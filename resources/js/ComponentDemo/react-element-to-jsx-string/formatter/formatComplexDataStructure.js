Object.defineProperty(exports, "__esModule", {
  value: true,
});

var _react = require("react");

var _stringifyObject = require("../stringifyObject");

var _stringifyObject2 = _interopRequireDefault(_stringifyObject);

var _sortObject = require("./sortObject");

var _sortObject2 = _interopRequireDefault(_sortObject);

var _parseReactElement = require("./../parser/parseReactElement");

var _parseReactElement2 = _interopRequireDefault(_parseReactElement);

var _formatTreeNode = require("./formatTreeNode");

var _formatTreeNode2 = _interopRequireDefault(_formatTreeNode);

var _spacer = require("./spacer");

var _spacer2 = _interopRequireDefault(_spacer);

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : { default: obj };
}

function noRefCheck() {}

exports.default = function(value, inline, lvl, options) {
  var normalizedValue = (0, _sortObject2.default)(value);

  var stringifiedValue = (0, _stringifyObject2.default)(normalizedValue, {
    transform: function transform(currentObj, prop, originalResult) {
      var currentValue = currentObj[prop];

      if (currentValue && (0, _react.isValidElement)(currentValue)) {
        return (0, _formatTreeNode2.default)(
          (0, _parseReactElement2.default)(currentValue, options),
          true,
          lvl,
          options
        );
      }

      if (typeof currentValue === "function") {
        return noRefCheck;
      }

      return originalResult;
    },
  });

  if (inline) {
    return stringifiedValue
      .replace(/\s+/g, " ")
      .replace(/{ /g, "{")
      .replace(/ }/g, "}")
      .replace(/\[ /g, "[")
      .replace(/ ]/g, "]");
  }

  // Replace tabs with spaces, and add necessary indentation in front of each new line
  return stringifiedValue
    .replace(/\t/g, (0, _spacer2.default)(1, options.tabStop))
    .replace(
      /\n([^$])/g,
      "\n" + (0, _spacer2.default)(lvl + 1, options.tabStop) + "$1"
    );
};
//# sourceMappingURL=formatComplexDataStructure.js.map
