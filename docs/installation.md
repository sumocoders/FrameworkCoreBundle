# Frontend installation

Install the frontend assets after cloning a project that uses this bundle.

## Prerequisites

- Node.js 20+ and npm 10+
- PHP 8.5+ with Symfony CLI

## Steps

```bash
git clone <git-repo>
symfony composer install
npm install
npm run build
```

## Development

Start the dev server with file watching:

```bash
npm run watch
```

Or using Webpack Encore hot module replacement:

```bash
npm run dev-server
```

## Troubleshooting

- **`npm install` fails** — ensure you are on Node 20+: `node --version`
- **Assets not updating** — run `php bin/console cache:clear` after changing SCSS variables
- **Missing `@sumocoders/framework-style-package`** — the npm package must be listed in `package.json`; run `npm install` again
