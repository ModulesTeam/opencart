#Maitenance (XNine)

##IntegrityCheck file

to generate integrityCheck file, run the command following:

```
php integrityDeploy.php '<PATH_AUTOLOAD>' "<DIR_IGNORED>, <DIR_IGNORED>"
```

The first argument after filename is the complet path to autoload. Ex: "path/autoload.php".

The second argument is the directories that will be ignored on integrity check. Ex: "path/vendor/, path/lib/, path/npm/".

Always separate the directories with comma.

Ex: php integrityDeploy.php 'path/autoload.php' "./lib/, ./vendor/, ./npm/"