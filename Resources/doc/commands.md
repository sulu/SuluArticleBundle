# Commands

## Reindex command

The reindex command can be used to rebuild the elastic-search index of the articles.

```bash
bin/adminconsole sulu:article:reindex
bin/websiteconsole sulu:article:reindex
```

The default behaviour of the command is to load all the articles and update the content of them. This includes that
deleted articles (which was not removed correctly from index) will stay inside the index.

To avoid this behaviour you can use the `--clear` option to remove all the articles from the index before reindex the
existing ones.

```bash
bin/adminconsole sulu:article:reindex --clear
bin/websiteconsole sulu:article:reindex --clear
```

Sometimes you want to update also the mapping of the index. To achieve this you can use the `--drop` option. This will
drop and recreate the index with the current mapping.

```bash
bin/adminconsole sulu:article:reindex --drop
bin/websiteconsole sulu:article:reindex --drop
```

This options will answer you the confirm this operation. To avoid this interaction you can use the `--no-interaction` 
option.

```bash
bin/adminconsole sulu:article:reindex --drop --no-interaction
bin/websiteconsole sulu:article:reindex --drop --no-interaction
```
