Object.defineProperty(exports, "__esModule", {
  value: true,
});

var _formatReactElementNode = require("./formatReactElementNode");

var _formatReactElementNode2 = _interopRequireDefault(_formatReactElementNode);

var _formatReactFragmentNode = require("./formatReactFragmentNode");

var _formatReactFragmentNode2 = _interopRequireDefault(
  _formatReactFragmentNode
);

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : { default: obj };
}

var jsxStopChars = ["<", ">", "{", "}"];
var shouldBeEscaped = function shouldBeEscaped(s) {
  return jsxStopChars.some(function(jsxStopChar) {
    return s.includes(jsxStopChar);
  });
};

var escape = function escape(s) {
  if (!shouldBeEscaped(s)) {
    return s;
  }

  return "{`" + s + "`}";
};

var preserveTrailingSpace = function preserveTrailingSpace(s) {
  var result = s;
  if (result.endsWith(" ")) {
    result = result.replace(/^(\S*)(\s*)$/, "$1{'$2'}");
  }

  if (result.startsWith(" ")) {
    result = result.replace(/^(\s*)(\S*)$/, "{'$1'}$2");
  }

  return result;
};

exports.default = function(node, inline, lvl, options) {
  if (node.type === "number") {
    return String(node.value);
  }

  if (node.type === "string") {
    return node.value
      ? "" + preserveTrailingSpace(escape(String(node.value)))
      : "";
  }

  if (node.type === "ReactElement") {
    return (0, _formatReactElementNode2.default)(node, inline, lvl, options);
  }

  if (node.type === "ReactFragment") {
    return (0, _formatReactFragmentNode2.default)(node, inline, lvl, options);
  }

  throw new TypeError('Unknow format type "' + node.type + '"');
};
//# sourceMappingURL=formatTreeNode.js.map
