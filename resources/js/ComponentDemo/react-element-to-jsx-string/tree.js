Object.defineProperty(exports, "__esModule", {
  value: true,
});

/* eslint-disable no-use-before-define */

var createStringTreeNode = (exports.createStringTreeNode = function createStringTreeNode( // eslint-disable-line no-unused-vars
  value
) {
  return {
    type: "string",
    value: value,
  };
});

var createNumberTreeNode = (exports.createNumberTreeNode = function createNumberTreeNode( // eslint-disable-line no-unused-vars
  value
) {
  return {
    type: "number",
    value: value,
  };
});

var createReactElementTreeNode = (exports.createReactElementTreeNode = function createReactElementTreeNode( // eslint-disable-line no-unused-vars
  displayName,
  props,
  defaultProps,
  childrens
) {
  return {
    type: "ReactElement",
    displayName: displayName,
    props: props,
    defaultProps: defaultProps,
    childrens: childrens,
  };
});

var createReactFragmentTreeNode = (exports.createReactFragmentTreeNode = function createReactFragmentTreeNode( // eslint-disable-line no-unused-vars
  key,
  childrens
) {
  return {
    type: "ReactFragment",
    key: key,
    childrens: childrens,
  };
});
//# sourceMappingURL=tree.js.map
