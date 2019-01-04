Object.defineProperty(exports, "__esModule", {
  value: true,
});

var _formatReactElementNode = require("./formatReactElementNode");

var _formatReactElementNode2 = _interopRequireDefault(_formatReactElementNode);

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : { default: obj };
}

var REACT_FRAGMENT_TAG_NAME_SHORT_SYNTAX = "";
var REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX = "React.Fragment";

var toReactElementTreeNode = function toReactElementTreeNode(
  displayName,
  key,
  childrens
) {
  var props = {};
  if (key) {
    props = { key: key };
  }

  return {
    type: "ReactElement",
    displayName: displayName,
    props: props,
    defaultProps: {},
    childrens: childrens,
  };
};

var isKeyedFragment = function isKeyedFragment(_ref) {
  var key = _ref.key;
  return Boolean(key);
};
var hasNoChildren = function hasNoChildren(_ref2) {
  var childrens = _ref2.childrens;
  return childrens.length === 0;
};

exports.default = function(node, inline, lvl, options) {
  var type = node.type,
    key = node.key,
    childrens = node.childrens;

  if (type !== "ReactFragment") {
    throw new Error(
      'The "formatReactFragmentNode" function could only format node of type "ReactFragment". Given: ' +
        type
    );
  }

  var useFragmentShortSyntax = options.useFragmentShortSyntax;

  var displayName = void 0;
  if (useFragmentShortSyntax) {
    if (hasNoChildren(node) || isKeyedFragment(node)) {
      displayName = REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX;
    } else {
      displayName = REACT_FRAGMENT_TAG_NAME_SHORT_SYNTAX;
    }
  } else {
    displayName = REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX;
  }

  return (0, _formatReactElementNode2.default)(
    toReactElementTreeNode(displayName, key, childrens),
    inline,
    lvl,
    options
  );
};
//# sourceMappingURL=formatReactFragmentNode.js.map
