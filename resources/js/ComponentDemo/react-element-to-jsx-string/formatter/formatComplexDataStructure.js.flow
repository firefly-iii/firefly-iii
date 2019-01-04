/* @flow */

import { isValidElement } from 'react';
import stringify from 'stringify-object';
import sortObject from './sortObject';
import parseReactElement from './../parser/parseReactElement';
import formatTreeNode from './formatTreeNode';
import spacer from './spacer';
import type { Options } from './../options';

function noRefCheck() {}

export default (
  value: Object | Array<any>,
  inline: boolean,
  lvl: number,
  options: Options
): string => {
  const normalizedValue = sortObject(value);

  const stringifiedValue = stringify(normalizedValue, {
    transform: (currentObj, prop, originalResult) => {
      const currentValue = currentObj[prop];

      if (currentValue && isValidElement(currentValue)) {
        return formatTreeNode(
          parseReactElement(currentValue, options),
          true,
          lvl,
          options
        );
      }

      if (typeof currentValue === 'function') {
        return noRefCheck;
      }

      return originalResult;
    },
  });

  if (inline) {
    return stringifiedValue
      .replace(/\s+/g, ' ')
      .replace(/{ /g, '{')
      .replace(/ }/g, '}')
      .replace(/\[ /g, '[')
      .replace(/ ]/g, ']');
  }

  // Replace tabs with spaces, and add necessary indentation in front of each new line
  return stringifiedValue
    .replace(/\t/g, spacer(1, options.tabStop))
    .replace(/\n([^$])/g, `\n${spacer(lvl + 1, options.tabStop)}$1`);
};
