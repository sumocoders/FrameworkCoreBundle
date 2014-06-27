class Data
  isInitialized: false
  data: null

  initialize: ->
    throw Error('jsData is not available') if not jsData?
    @data = jsData
    @isInitialized = true
  false

  exists: (key) ->
    @get(key)?

  get: (key) ->
    @initialize() if not @isInitialized
    @data[key]

Data.current = new Data

window.Data = Data.current
