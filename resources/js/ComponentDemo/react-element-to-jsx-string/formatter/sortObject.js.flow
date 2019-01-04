/* @flow */

export default function sortObject(value: any): any {
  // return non-object value as is
  if (value === null || typeof value !== 'object') {
    return value;
  }

  // return date and regexp values as is
  if (value instanceof Date || value instanceof RegExp) {
    return value;
  }

  // make a copy of array with each item passed through sortObject()
  if (Array.isArray(value)) {
    return value.map(sortObject);
  }

  // make a copy of object with key sorted
  return Object.keys(value)
    .sort()
    .reduce((result, key) => {
      // eslint-disable-next-line no-param-reassign
      result[key] = sortObject(value[key]);
      return result;
    }, {});
}
