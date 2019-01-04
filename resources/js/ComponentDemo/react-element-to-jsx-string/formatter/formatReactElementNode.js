Object.defineProperty(exports, "__esModule", {
  value: true,
});

var _spacer = require("./spacer");

var _spacer2 = _interopRequireDefault(_spacer);

var _formatTreeNode = require("./formatTreeNode");

var _formatTreeNode2 = _interopRequireDefault(_formatTreeNode);

var _formatProp2 = require("./formatProp");

var _formatProp3 = _interopRequireDefault(_formatProp2);

var _mergeSiblingPlainStringChildrenReducer = require("./mergeSiblingPlainStringChildrenReducer");

var _mergeSiblingPlainStringChildrenReducer2 = _interopRequireDefault(
  _mergeSiblingPlainStringChildrenReducer
);

var _propNameSorter = require("./propNameSorter");

var _propNameSorter2 = _interopRequireDefault(_propNameSorter);

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : { default: obj };
}

var compensateMultilineStringElementIndentation = function compensateMultilineStringElementIndentation(
  element,
  formattedElement,
  inline,
  lvl,
  options
) {
  var tabStop = options.tabStop;

  if (element.type === "string") {
    return formattedElement
      .split("\n")
      .map(function(line, offset) {
        if (offset === 0) {
          return line;
        }

        return "" + (0, _spacer2.default)(lvl, tabStop) + line;
      })
      .join("\n");
  }

  return formattedElement;
};

var formatOneChildren = function formatOneChildren(inline, lvl, options) {
  return function(element) {
    return compensateMultilineStringElementIndentation(
      element,
      (0, _formatTreeNode2.default)(element, inline, lvl, options),
      inline,
      lvl,
      options
    );
  };
};

var onlyPropsWithOriginalValue = function onlyPropsWithOriginalValue(
  defaultProps,
  props
) {
  return function(propName) {
    var haveDefaultValue = Object.keys(defaultProps).includes(propName);
    return (
      !haveDefaultValue ||
      (haveDefaultValue && defaultProps[propName] !== props[propName])
    );
  };
};

var isInlineAttributeTooLong = function isInlineAttributeTooLong(
  attributes,
  inlineAttributeString,
  lvl,
  tabStop,
  maxInlineAttributesLineLength
) {
  if (!maxInlineAttributesLineLength) {
    return attributes.length > 1;
  }

  return (
    (0, _spacer2.default)(lvl, tabStop).length + inlineAttributeString.length >
    maxInlineAttributesLineLength
  );
};

var shouldRenderMultilineAttr = function shouldRenderMultilineAttr(
  attributes,
  inlineAttributeString,
  containsMultilineAttr,
  inline,
  lvl,
  tabStop,
  maxInlineAttributesLineLength
) {
  return (
    (isInlineAttributeTooLong(
      attributes,
      inlineAttributeString,
      lvl,
      tabStop,
      maxInlineAttributesLineLength
    ) ||
      containsMultilineAttr) &&
    !inline
  );
};

exports.default = function(node, inline, lvl, options) {
  var type = node.type,
    _node$displayName = node.displayName,
    displayName = _node$displayName === undefined ? "" : _node$displayName,
    childrens = node.childrens,
    _node$props = node.props,
    props = _node$props === undefined ? {} : _node$props,
    _node$defaultProps = node.defaultProps,
    defaultProps = _node$defaultProps === undefined ? {} : _node$defaultProps;

  if (type !== "ReactElement") {
    throw new Error(
      'The "formatReactElementNode" function could only format node of type "ReactElement". Given:  ' +
        type
    );
  }

  var filterProps = options.filterProps,
    maxInlineAttributesLineLength = options.maxInlineAttributesLineLength,
    showDefaultProps = options.showDefaultProps,
    sortProps = options.sortProps,
    tabStop = options.tabStop;

  var out = "<" + displayName;

  var outInlineAttr = out;
  var outMultilineAttr = out;
  var containsMultilineAttr = false;

  var visibleAttributeNames = [];

  Object.keys(props)
    .filter(function(propName) {
      return filterProps.indexOf(propName) === -1;
    })
    .filter(onlyPropsWithOriginalValue(defaultProps, props))
    .forEach(function(propName) {
      return visibleAttributeNames.push(propName);
    });

  Object.keys(defaultProps)
    .filter(function(defaultPropName) {
      return filterProps.indexOf(defaultPropName) === -1;
    })
    .filter(function() {
      return showDefaultProps;
    })
    .filter(function(defaultPropName) {
      return !visibleAttributeNames.includes(defaultPropName);
    })
    .forEach(function(defaultPropName) {
      return visibleAttributeNames.push(defaultPropName);
    });

  var attributes = visibleAttributeNames.sort(
    (0, _propNameSorter2.default)(sortProps)
  );

  attributes.forEach(function(attributeName) {
    var _formatProp = (0, _formatProp3.default)(
        attributeName,
        Object.keys(props).includes(attributeName),
        props[attributeName],
        Object.keys(defaultProps).includes(attributeName),
        defaultProps[attributeName],
        inline,
        lvl,
        options
      ),
      attributeFormattedInline = _formatProp.attributeFormattedInline,
      attributeFormattedMultiline = _formatProp.attributeFormattedMultiline,
      isMultilineAttribute = _formatProp.isMultilineAttribute;

    if (isMultilineAttribute) {
      containsMultilineAttr = true;
    }

    outInlineAttr += attributeFormattedInline;
    outMultilineAttr += attributeFormattedMultiline;
  });

  outMultilineAttr += "\n" + (0, _spacer2.default)(lvl, tabStop);

  if (
    shouldRenderMultilineAttr(
      attributes,
      outInlineAttr,
      containsMultilineAttr,
      inline,
      lvl,
      tabStop,
      maxInlineAttributesLineLength
    )
  ) {
    out = outMultilineAttr;
  } else {
    out = outInlineAttr;
  }

  if (childrens && childrens.length > 0) {
    var newLvl = lvl + 1;

    out += ">";

    if (!inline) {
      out += "\n";
      out += (0, _spacer2.default)(newLvl, tabStop);
    }

    out += childrens
      .reduce(_mergeSiblingPlainStringChildrenReducer2.default, [])
      .map(formatOneChildren(inline, newLvl, options))
      .join(!inline ? "\n" + (0, _spacer2.default)(newLvl, tabStop) : "");

    if (!inline) {
      out += "\n";
      out += (0, _spacer2.default)(newLvl - 1, tabStop);
    }
    out += "</" + displayName + ">";
  } else {
    if (
      !isInlineAttributeTooLong(
        attributes,
        outInlineAttr,
        lvl,
        tabStop,
        maxInlineAttributesLineLength
      )
    ) {
      out += " ";
    }

    out += "/>";
  }

  return out;
};
//# sourceMappingURL=formatReactElementNode.js.map
