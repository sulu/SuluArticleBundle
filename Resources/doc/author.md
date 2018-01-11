# Author

The author is a value which can be set by the content-manager. By default set the creator as the author on creation time
and the content-manager can select another contact in the settings-tab. Therefor the author is a prefilled mandatory
value.

This behavior can be influenced by the configuration and make the author an optional nullable value.
 
```yaml
sulu_article:
    default_author: false
```

When this configuration isset the author will no be prefilled and can be set optional in the settings-tab. If is was not
set the `author` variable in twig will stay `null`.
