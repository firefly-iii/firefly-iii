/* @flow */

import spacer from './spacer';
import formatTreeNode from './formatTreeNode';
import formatProp from './formatProp';
import mergeSiblingPlainStringChildrenReducer from './mergeSiblingPlainStringChildrenReducer';
import propNameSorter from './propNameSorter';
import type { Options } from './../options';
import type { ReactElementTreeNode } from './../tree';

const compensateMultilineStringElementIndentation = (
  element,
  formattedElement: string,
  inline: boolean,
  lvl: number,
  options: Options
) => {
  const { tabStop } = options;

  if (element.type === 'string') {
    return formattedElement
      .split('\n')
      .map((line, offset) => {
        if (offset === 0) {
          return line;
        }

        return `${spacer(lvl, tabStop)}${line}`;
      })
      .join('\n');
  }

  return formattedElement;
};

const formatOneChildren = (
  inline: boolean,
  lvl: number,
  options: Options
) => element =>
  compensateMultilineStringElementIndentation(
    element,
    formatTreeNode(element, inline, lvl, options),
    inline,
    lvl,
    options
  );

const onlyPropsWithOriginalValue = (defaultProps, props) => propName => {
  const haveDefaultValue = Object.keys(defaultProps).includes(propName);
  return (
    !haveDefaultValue ||
    (haveDefaultValue && defaultProps[propName] !== props[propName])
  );
};

const isInlineAttributeTooLong = (
  attributes: string[],
  inlineAttributeString: string,
  lvl: number,
  tabStop: number,
  maxInlineAttributesLineLength: ?number
): boolean => {
  if (!maxInlineAttributesLineLength) {
    return attributes.length > 1;
  }

  return (
    spacer(lvl, tabStop).length + inlineAttributeString.length >
    maxInlineAttributesLineLength
  );
};

const shouldRenderMultilineAttr = (
  attributes: string[],
  inlineAttributeString: string,
  containsMultilineAttr: boolean,
  inline: boolean,
  lvl: number,
  tabStop: number,
  maxInlineAttributesLineLength: ?number
): boolean =>
  (isInlineAttributeTooLong(
    attributes,
    inlineAttributeString,
    lvl,
    tabStop,
    maxInlineAttributesLineLength
  ) ||
    containsMultilineAttr) &&
  !inline;

export default (
  node: ReactElementTreeNode,
  inline: boolean,
  lvl: number,
  options: Options
): string => {
  const {
    type,
    displayName = '',
    childrens,
    props = {},
    defaultProps = {},
  } = node;

  if (type !== 'ReactElement') {
    throw new Error(
      `The "formatReactElementNode" function could only format node of type "ReactElement". Given:  ${
        type
      }`
    );
  }

  const {
    filterProps,
    maxInlineAttributesLineLength,
    showDefaultProps,
    sortProps,
    tabStop,
  } = options;

  let out = `<${displayName}`;

  let outInlineAttr = out;
  let outMultilineAttr = out;
  let containsMultilineAttr = false;

  const visibleAttributeNames = [];

  Object.keys(props)
    .filter(propName => filterProps.indexOf(propName) === -1)
    .filter(onlyPropsWithOriginalValue(defaultProps, props))
    .forEach(propName => visibleAttributeNames.push(propName));

  Object.keys(defaultProps)
    .filter(defaultPropName => filterProps.indexOf(defaultPropName) === -1)
    .filter(() => showDefaultProps)
    .filter(defaultPropName => !visibleAttributeNames.includes(defaultPropName))
    .forEach(defaultPropName => visibleAttributeNames.push(defaultPropName));

  const attributes = visibleAttributeNames.sort(propNameSorter(sortProps));

  attributes.forEach(attributeName => {
    const {
      attributeFormattedInline,
      attributeFormattedMultiline,
      isMultilineAttribute,
    } = formatProp(
      attributeName,
      Object.keys(props).includes(attributeName),
      props[attributeName],
      Object.keys(defaultProps).includes(attributeName),
      defaultProps[attributeName],
      inline,
      lvl,
      options
    );

    if (isMultilineAttribute) {
      containsMultilineAttr = true;
    }

    outInlineAttr += attributeFormattedInline;
    outMultilineAttr += attributeFormattedMultiline;
  });

  outMultilineAttr += `\n${spacer(lvl, tabStop)}`;

  if (
    shouldRenderMultilineAttr(
      attributes,
      outInlineAttr,
      containsMultilineAttr,
      inline,
      lvl,
      tabStop,
      maxInlineAttributesLineLength
    )
  ) {
    out = outMultilineAttr;
  } else {
    out = outInlineAttr;
  }

  if (childrens && childrens.length > 0) {
    const newLvl = lvl + 1;

    out += '>';

    if (!inline) {
      out += '\n';
      out += spacer(newLvl, tabStop);
    }

    out += childrens
      .reduce(mergeSiblingPlainStringChildrenReducer, [])
      .map(formatOneChildren(inline, newLvl, options))
      .join(!inline ? `\n${spacer(newLvl, tabStop)}` : '');

    if (!inline) {
      out += '\n';
      out += spacer(newLvl - 1, tabStop);
    }
    out += `</${displayName}>`;
  } else {
    if (
      !isInlineAttributeTooLong(
        attributes,
        outInlineAttr,
        lvl,
        tabStop,
        maxInlineAttributesLineLength
      )
    ) {
      out += ' ';
    }

    out += '/>';
  }

  return out;
};
