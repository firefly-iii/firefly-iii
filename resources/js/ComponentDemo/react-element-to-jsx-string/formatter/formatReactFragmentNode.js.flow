/* @flow */

import type { Key } from 'react';
import formatReactElementNode from './formatReactElementNode';
import type { Options } from './../options';
import type {
  ReactElementTreeNode,
  ReactFragmentTreeNode,
  TreeNode,
} from './../tree';

const REACT_FRAGMENT_TAG_NAME_SHORT_SYNTAX = '';
const REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX = 'React.Fragment';

const toReactElementTreeNode = (
  displayName: string,
  key: ?Key,
  childrens: TreeNode[]
): ReactElementTreeNode => {
  let props = {};
  if (key) {
    props = { key };
  }

  return {
    type: 'ReactElement',
    displayName,
    props,
    defaultProps: {},
    childrens,
  };
};

const isKeyedFragment = ({ key }: ReactFragmentTreeNode) => Boolean(key);
const hasNoChildren = ({ childrens }: ReactFragmentTreeNode) =>
  childrens.length === 0;

export default (
  node: ReactFragmentTreeNode,
  inline: boolean,
  lvl: number,
  options: Options
): string => {
  const { type, key, childrens } = node;

  if (type !== 'ReactFragment') {
    throw new Error(
      `The "formatReactFragmentNode" function could only format node of type "ReactFragment". Given: ${
        type
      }`
    );
  }

  const { useFragmentShortSyntax } = options;

  let displayName;
  if (useFragmentShortSyntax) {
    if (hasNoChildren(node) || isKeyedFragment(node)) {
      displayName = REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX;
    } else {
      displayName = REACT_FRAGMENT_TAG_NAME_SHORT_SYNTAX;
    }
  } else {
    displayName = REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX;
  }

  return formatReactElementNode(
    toReactElementTreeNode(displayName, key, childrens),
    inline,
    lvl,
    options
  );
};
