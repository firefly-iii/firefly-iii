Object.defineProperty(exports, "__esModule", {
  value: true,
});

var _formatTree = require("./formatter/formatTree");

var _formatTree2 = _interopRequireDefault(_formatTree);

var _parseReactElement = require("./parser/parseReactElement");

var _parseReactElement2 = _interopRequireDefault(_parseReactElement);

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : { default: obj };
}

var reactElementToJsxString = function reactElementToJsxString(element) {
  var _ref =
      arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
    _ref$filterProps = _ref.filterProps,
    filterProps = _ref$filterProps === undefined ? [] : _ref$filterProps,
    _ref$showDefaultProps = _ref.showDefaultProps,
    showDefaultProps =
      _ref$showDefaultProps === undefined ? true : _ref$showDefaultProps,
    _ref$showFunctions = _ref.showFunctions,
    showFunctions =
      _ref$showFunctions === undefined ? false : _ref$showFunctions,
    functionValue = _ref.functionValue,
    _ref$tabStop = _ref.tabStop,
    tabStop = _ref$tabStop === undefined ? 2 : _ref$tabStop,
    _ref$useBooleanShorth = _ref.useBooleanShorthandSyntax,
    useBooleanShorthandSyntax =
      _ref$useBooleanShorth === undefined ? true : _ref$useBooleanShorth,
    _ref$useFragmentShort = _ref.useFragmentShortSyntax,
    useFragmentShortSyntax =
      _ref$useFragmentShort === undefined ? true : _ref$useFragmentShort,
    _ref$sortProps = _ref.sortProps,
    sortProps = _ref$sortProps === undefined ? true : _ref$sortProps,
    maxInlineAttributesLineLength = _ref.maxInlineAttributesLineLength,
    displayName = _ref.displayName;

  if (!element) {
    throw new Error("react-element-to-jsx-string: Expected a ReactElement");
  }

  var options = {
    filterProps: filterProps,
    showDefaultProps: showDefaultProps,
    showFunctions: showFunctions,
    functionValue: functionValue,
    tabStop: tabStop,
    useBooleanShorthandSyntax: useBooleanShorthandSyntax,
    useFragmentShortSyntax: useFragmentShortSyntax,
    sortProps: sortProps,
    maxInlineAttributesLineLength: maxInlineAttributesLineLength,
    displayName: displayName,
  };

  return (0, _formatTree2.default)(
    (0, _parseReactElement2.default)(element, options),
    options
  );
};

exports.default = reactElementToJsxString;
//# sourceMappingURL=index.js.map
