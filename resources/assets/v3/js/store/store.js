let engine = require('store/src/store-engine')

let storages = require('store/storages/all')
let plugins = [require('store/plugins/observe')]

module.exports = engine.createStore(storages, plugins)
