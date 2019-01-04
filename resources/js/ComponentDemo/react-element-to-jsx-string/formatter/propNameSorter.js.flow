/* @flow */

export default (sortProps: boolean) => (a: string, b: string): -1 | 0 | 1 => {
  if (a === b) {
    return 0;
  }

  if (['key', 'ref'].includes(a)) {
    return -1;
  } else if (['key', 'ref'].includes(b)) {
    return 1;
  }

  if (!sortProps) {
    return 0;
  }

  return a < b ? -1 : 1;
};
