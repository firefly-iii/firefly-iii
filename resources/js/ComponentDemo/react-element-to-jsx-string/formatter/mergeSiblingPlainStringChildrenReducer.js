Object.defineProperty(exports, "__esModule", {
  value: true,
});

var _tree = require("./../tree");

exports.default = function(previousNodes, currentNode) {
  var nodes = previousNodes.slice(
    0,
    previousNodes.length > 0 ? previousNodes.length - 1 : 0
  );
  var previousNode = previousNodes[previousNodes.length - 1];

  if (
    previousNode &&
    (currentNode.type === "string" || currentNode.type === "number") &&
    (previousNode.type === "string" || previousNode.type === "number")
  ) {
    nodes.push(
      (0, _tree.createStringTreeNode)(
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
//# sourceMappingURL=mergeSiblingPlainStringChildrenReducer.js.map
