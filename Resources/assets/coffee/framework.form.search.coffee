class SearchForm extends Form
  constructor: ->
    @_initSearch()

  _initSearch: ->
    $searchField = $('.searchBox input[name=q]', @form);

    $searchField.autocomplete(
      position:
        using: (position, elements) ->
          newPosition =
            left: position.left
            top: 'auto'
            bottom: elements.target.height
            margin: 0
          elements.element.element.css(newPosition)
      source: (request, response) ->
        # @todo correct route
        $.ajax
          type: 'GET'
          url: '/' + Data.get('request.locale') + '/search.json'
          data: { q: request.term }
          success: (data) ->
            items = []
            for value in data.data.results
              items.push(
                {
                  value: value
                }
              )
            response(items)
      select: (e, ui) ->
        e.preventDefault()
        if ui.item.value.route?
          document.location = ui.item.value.route
        else if ui.item.value.value?
          return ui.item.value.value
        else
          return ui.item.label
      focus: (e, ui) ->
        e.preventDefault()
        $(e.target).val(ui.item.value.title)
    )

    $searchField.each (idx,element) =>
      $(element).data('ui-autocomplete')._renderItem = @renderItem

    return

  renderItem: (ul, item) ->
    $('<li>')
      .append(
        $('<a>').append(
          item.value.title +
          '<small class="muted"> (' + item.value.bundle + ')</small>'
        )
      )
      .appendTo(ul)


window.SearchForm = SearchForm
