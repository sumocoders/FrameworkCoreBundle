class Locale
  isInitialized: false
  data: null

  initialize: ->
    $.ajax '/' + Data.get('request.locale') + '/locale.json',
      type: 'GET'
      dataType: 'json'
      async: false,
      success: (data) =>
        @data = data
        @isInitialized = true
      error: (jqXHR, textStatus, errorThrown) =>
        throw Error('Regenerate your locale-files.')
  false

  exists: (key) ->
    @get(key)?

  get: (key) ->
    @initialize() if not @isInitialized
    return @data[key] if @data[key]?
    key

  act: (key) ->
    @get(key)

  err: (key) ->
    @get( key)

  lbl: (key) ->
    @get(key)

  loc: (key) ->
    @get(key)

  msg: (key) ->
    @get(key)

window.Locale = new Locale
