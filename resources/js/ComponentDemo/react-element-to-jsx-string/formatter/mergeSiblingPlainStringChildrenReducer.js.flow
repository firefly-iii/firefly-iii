/* @flow */

import { createStringTreeNode } from './../tree';
import type { TreeNode } from './../tree';

export default (
  previousNodes: TreeNode[],
  currentNode: TreeNode
): TreeNode[] => {
  const nodes = previousNodes.slice(
    0,
    previousNodes.length > 0 ? previousNodes.length - 1 : 0
  );
  const previousNode = previousNodes[previousNodes.length - 1];

  if (
    previousNode &&
    (currentNode.type === 'string' || currentNode.type === 'number') &&
    (previousNode.type === 'string' || previousNode.type === 'number')
  ) {
    nodes.push(
      createStringTreeNode(
        String(previousNode.value) + String(currentNode.value)
      )
    );
  } else {
    if (previousNode) {
      nodes.push(previousNode);
    }

    nodes.push(currentNode);
  }

  return nodes;
};
