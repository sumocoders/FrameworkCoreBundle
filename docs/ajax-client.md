# AJAX client

The AJAX client is a simple wrapper around [`Axios`](https://axios-http.com/).

## Default Axios Config

Some configuration is done by default. But it can be overruled.

* `timeout`: 2500
* `headers.common`: `Accept: application/json`

## Usage

The AJAX Client is just an extended version of the Axios client all Axios
documentation is still valid. For the full documentatuon see
[https://axios-http.com/docs/intro](https://axios-http.com/docs/intro).

A simplified Stimulus controller that uses the AJAX Client:

```javascript
import { Controller } from '@hotwired/stimulus'
import ajaxClient from '../js/ajax_client.js'

export default class extends Controller {
  static values = {
    url: String,
    csrfToken: String
  }

  static targets = ['button']

  test () {
    let data = {
      foo: 'bar',
    }

    ajaxClient.csrf_token = this.csrfTokenValue
    ajaxClient.busy_targets = this.buttonTargets
    ajaxClient.post(this.urlValue, data)
      .then(response => {
        // do something with the response
      ...
      })
      .catch(error => {
        // do something with the error
      ...
      })
  }
}
```

## Default toasts

Depending on HTTP status code and the provided data a toast will be shown. This
is done by using [Interceptors](https://axios-http.com/docs/interceptors). So
you can still use the promises of the Axios client.

### Success (HTTP Status 2XX)

If the response object contains a `message` key, a success toast will be shown.

You can return a response like this:

```php
  return new JsonResponse(
    [
      'message' => 'The action was successful.',
    ],
    200
  );
```

The actual JSON will be:

```json
{
  "message": "The action was successful."
}
```

### Error (HTTP Status != 2XX)

If the response object contains a `message` key, an danger toast will be shown.
If the message is not present the Exception message will be used.

```php
  return new JsonResponse(
    [
      'message' => 'Item not found.',
    ],
    404
  );
```

or

```php
  throw new \RuntimeException('Item not found.');
```

### Disable this behavior

You can disable the toast by passing a `disable_interceptor: true` in the response data.

```json
{
  "message": "The action was successful",
  "disable_interceptor": true
}
```

## CSRF token

A simple way to "protect" the AJAX calls is by using a CSRF token. This is done like below:

```javascript
ajaxClient.csrf_token = this.csrfTokenValue
ajaxClient.post(this.urlValue, data)
  ...
```

With this the csrf token is added to the payload of the request, with the key `csrf_token`.

In your controller you will need to check if the CSRF token is valid:

```php
  if (!$this->isCsrfTokenValid('this-is-our-csrf-token-id', $request->getPayload()->get('csrf_token'))) {
    throw new InvalidCsrfTokenException('Invalid CSRF token');
  }
```

## Busy button spinner

The content of a clicked button can be replaced by a spinner during the request. Pass the DOM Node(s) like below:

```javascript
ajaxClient.busy_targets = [buttonNode]
ajaxClient.post(this.urlValue, data)
  ...
```
