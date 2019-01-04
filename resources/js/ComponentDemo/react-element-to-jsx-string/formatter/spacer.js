Object.defineProperty(exports, "__esModule", {
  value: true,
});

exports.default = function(times, tabStop) {
  if (times === 0) {
    return "";
  }

  return new Array(times * tabStop).fill(" ").join("");
};
//# sourceMappingURL=spacer.js.map
