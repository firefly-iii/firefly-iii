Object.defineProperty(exports, "__esModule", {
  value: true,
});

exports.default = function(sortProps) {
  return function(a, b) {
    if (a === b) {
      return 0;
    }

    if (["key", "ref"].includes(a)) {
      return -1;
    } else if (["key", "ref"].includes(b)) {
      return 1;
    }

    if (!sortProps) {
      return 0;
    }

    return a < b ? -1 : 1;
  };
};
//# sourceMappingURL=propNameSorter.js.map
