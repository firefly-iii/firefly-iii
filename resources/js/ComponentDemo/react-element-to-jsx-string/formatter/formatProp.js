Object.defineProperty(exports, "__esModule", {
  value: true,
});

var _spacer = require("./spacer");

var _spacer2 = _interopRequireDefault(_spacer);

var _formatPropValue = require("./formatPropValue");

var _formatPropValue2 = _interopRequireDefault(_formatPropValue);

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : { default: obj };
}

exports.default = function(
  name,
  hasValue,
  value,
  hasDefaultValue,
  defaultValue,
  inline,
  lvl,
  options
) {
  if (!hasValue && !hasDefaultValue) {
    throw new Error(
      'The prop "' +
        name +
        '" has no value and no default: could not be formatted'
    );
  }

  var usedValue = hasValue ? value : defaultValue;

  var useBooleanShorthandSyntax = options.useBooleanShorthandSyntax,
    tabStop = options.tabStop;

  var formattedPropValue = (0, _formatPropValue2.default)(
    usedValue,
    inline,
    lvl,
    options
  );

  var attributeFormattedInline = " ";
  var attributeFormattedMultiline =
    "\n" + (0, _spacer2.default)(lvl + 1, tabStop);
  var isMultilineAttribute = formattedPropValue.includes("\n");

  if (
    useBooleanShorthandSyntax &&
    formattedPropValue === "{false}" &&
    !hasDefaultValue
  ) {
    // If a boolean is false and not different from it's default, we do not render the attribute
    attributeFormattedInline = "";
    attributeFormattedMultiline = "";
  } else if (useBooleanShorthandSyntax && formattedPropValue === "{true}") {
    attributeFormattedInline += "" + name;
    attributeFormattedMultiline += "" + name;
  } else {
    attributeFormattedInline += name + "=" + formattedPropValue;
    attributeFormattedMultiline += name + "=" + formattedPropValue;
  }

  return {
    attributeFormattedInline: attributeFormattedInline,
    attributeFormattedMultiline: attributeFormattedMultiline,
    isMultilineAttribute: isMultilineAttribute,
  };
};
//# sourceMappingURL=formatProp.js.map
