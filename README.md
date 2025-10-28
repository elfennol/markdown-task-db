# MarkdownTaskDb

KISS approach to manage tasks and track your time in markdown. POC in PHP (final version in Rust?).

## Description

The following command

```sh
bin/console markdown-task-db markdown_directory
```

takes the markdown files in the directory `markdown_directory`, extracts some data from these files and inserts these data in DB (SQLite). You can then make your own stats just with SQL queries.

- Sample queries: [tests/MarkdownTaskDbTest.php](tests/MarkdownTaskDbTest.php).
- DB schema: [src/Service/SchemaBuilder.php](src/Service/SchemaBuilder.php).
- DB path: [.env](.env) (`DATABASE_URL`).

Each of these markdown files must have a specific structure.

- Example: [tests/fixtures/project.md](tests/fixtures/project.md).

### With Docker

If you do not have the correct PHP version, you can use Docker. For example, create a directory `mtd` in `var/` and put your markdown files here.

Install the project before:

```sh
docker container run --rm -v $(pwd):/app/ -u $(id -u ${USER}):$(id -g ${USER}) composer install

```

Then you can execute the command:

```sh
docker container run --rm -v $(pwd):/app/  -u $(id -u ${USER}):$(id -g ${USER}) php:8.4-cli /app/bin/console markdown-task-db /app/var/mtd/

```

## Rules

- Each required JSON after the heading contains your own metadata with your own fields.
- The JSON is required but can be empty.
- Exception: the JSON after the heading `### Tracking` has a required format (start date, end date, your own metadata).
- The first heading contains the project name.
- Tasks are under the heading `# Tasks`.
- The second level headings under `# Tasks` contain the task name.
- Under each task is a JSON file for metadata.
- Tracking metadata is under the heading `### Tracking`.

## Install project

- `composer install`.

## QA

- Help: `make help`
- Install QA tools: `make qa-vendor-install`.
- Must succeed (`make qa`): phpcs, phpunit, rector.
- Optional (just an indicator to improve the code): phpstan.

## TODO

- Write a more robust version in Rust.
- Check with extension `*.mtd.md` in `FileFinder` ?
- In `MarkdownTaskDbTest.php`, round with 1 (not 0).
