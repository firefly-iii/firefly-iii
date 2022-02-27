/*
export function someMutation (state) {
}
*/

export const updateViewRange = (state, viewRange) => {
  state.viewRange = viewRange;
}

export const updateListPageSize = (state, value) => {
  state.listPageSize = value;
}

export const setRange = (state, value) => {
  state.range = value;
}

export const setDefaultRange = (state, value) => {
  state.defaultRange = value;
}

export const setCurrencyCode = (state, value) => {
  state.currencyCode = value;
}
export const setCurrencyId = (state, value) => {
  state.currencyId = value;
}

export const setCacheKey = (state, value) => {
  state.cacheKey = value;
}
