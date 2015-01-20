class Locale
  isInitialized: false
  data: null

  initialize: ->
    $.ajax '/cache/locale/' + Data.get('request.locale') + '.json',
      type: 'GET'
      dataType: 'json'
      async: false,
      success: (data) =>
        @data = data
        @isInitialized = true
      error: (jqXHR, textStatus, errorThrown) =>
        # throw Error('Regenerate your locale-files.')
        @isInitialized = true
  false

  exists: (key) ->
    @get(key)?

  get: (type, key) ->
    @initialize() if not @isInitialized
    return @data[type][key] if @data? and @data.type? and @data.type.key?
    '{$' + type + key + '}'

  act: (key) ->
    @get('act', key)

  err: (key) ->
    @get('err', key)

  lbl: (key) ->
    @get('lbl', key)

  loc: (key) ->
    @get('loc', key)

  msg: (key) ->
    @get('msg', key)

window.Locale = new Locale
