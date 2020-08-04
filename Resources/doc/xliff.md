# XLIFF Export / Import

The SuluArticleBundle is able to export the content of the articles into a XLIFF format. The resulting file can be used
by a translation tool or send to a translation agency.

They can extend the file with the translation and after that imported by the developer.  

__Export:__

```bash
$ bin/adminconsole sulu:article:export export.xliff en
Article Language Export
=======================
Options
Target: export.xliff
Locale: en
Format: 1.2.xliff
---------------
Continue with this options?(y/n) y
Continue!
Loading Data…
 30/30 [============================] 100%
Render Xliff…
```

__Import:__

```bash
$ bin/adminconsole sulu:article:import export.xliff de --overrideSettings
Language Import
===============

Options
Locale: de
Format: 1.2.xliff
Override Setting: YES
---------------

Continue with this options? Be careful! (y/n) y
Continue!
 1/1 [============================] 100% 2 secs/2 secs 54.5 MiB

Import Result
===============
1 Documents imported.
0 Documents ignored.
```
