# Creating PDFs


You can render pdfs with either chrome or wkhtmltopdf using
[ChromePdfBundle](https://github.com/dreadnip/chrome-pdf-bundle)
or
[KnpSnappyBundle](https://github.com/KnpLabs/KnpSnappyBundle)
respectively.

## Bugs

When generating multiple files and using `encore_entry_css_files` in the twig template, this bug occurs:

[WebpackEncoreBundle issue #33: encore_entry_css_files returns nothing if called multiple times](https://github.com/symfony/webpack-encore-bundle/issues/33)

### Fix
Call reset on EntrypointLookupInterface before each render.

Example:
```php
public function __construct(
    private readonly Environment $twig,
    private readonly PdfGenerator $pdfGenerator,
    private readonly EntrypointLookupInterface $entrypointLookup,
) {}

public function renderAsHtml(...)
{
    $this->entrypointLookup->reset()
    $this->twig->render(...
```

