# Customize folder

Per company **GUI** customizations: which fields a screen shows, their labels, options and order.
The database always has every field. A customization only decides what the screen uses.

```
customize/
  _example/                 examples of every option (not a company)
  hidro-projekt/            one folder per company (folder name = company)
    workplace_fields.json   Employees → Workplaces (contracts)
```

## Which company is active

`APP_COMPANY` in `.env` (default `hidro-projekt`), see `config/customize.php`.
Later this comes from the company table. `CustomizeService::currentCompany()` is the only
place that has to change for that.

## Rules

- **No file = no customization:** the screen shows everything.
- A file with only a `"$comment"` (like `hidro-projekt/workplace_fields.json`) also shows everything.
  `"$comment"` is ignored everywhere, so use it for notes.
- **Invalid JSON is not ignored.** The screen shows everything and a warning with the error until the
  file is fixed.
- Folder and file names: letters, numbers, `-` and `_` only.
- Nothing here is secret: the files are committed and deployed with the code.

## workplace_fields.json

The full field list (with numbers, tabs and types) is in
`App\Services\Config\WorkplaceFieldsConfigService::CATALOG`. Every part is optional.

| Key | What it does |
|---|---|
| `only` | Show **only** these fields. The core fields `key`, `name`, `active` are always shown. |
| `tabs` | `"tab": false` hides a tab, `"tab": { "label": "…" }` renames it. Tabs: `basic`, `contract`, `requirements`, `pay`, `workers`. `basic` can't be hidden. A tab without fields disappears. |
| `fields` | `"field": false` hides a field. `"field": { … }` changes `label`, `help` (tooltip), `options` (select / checks), `required`, `tab` (move it). |
| `order` | `"tab": ["field", …]` order inside a tab. Fields not listed follow in the default order. |
| `custom` | The company's own fields: `{ "tab", "label", "type", "options", "help", "required" }`. Types: `text`, `textarea`, `number`, `date`, `select`, `switch`, `checks`, `range`. **Prototype only**: the database has no column for them yet. |

Unknown fields, tabs or types are ignored and listed as warnings on the screen
(*Field configuration → Show JSON*).

Full example: [`_example/workplace_fields.json`](_example/workplace_fields.json).

## Adding a new customizable screen

1. Put the screen's full field list in a service, like `WorkplaceFieldsConfigService::CATALOG`.
2. Read the company's file with `CustomizeService::get('<name>')` (returns `NULL` when there is none).
3. Document the file here.
