import TomSelect from 'tom-select'

const SelectSearch = function (element) {
  const url = this.element.dataset.route

  this.select = new TomSelect(this.element, {
    valueField: 'id',
    shouldLoad: function (query) {
      return query.length > 3
    },
    load: function (query, callback) {
      const route = url + encodeURIComponent(query)
      fetch(route)
        .then(response => response.json())
        .then(json => {
          callback(json)
        }).catch(() => {
          callback()
        })
    },
    render: {
      option: function (item, escape) {
        return '<option>' + escape(item.text) + '</option>'
      },
      no_results: function (item, escape) {
        return '<div class="no-results">Geen resultaten voor "' + escape(item.input) + '"</div>'
      }
    }
  })
}

export default SelectSearch
