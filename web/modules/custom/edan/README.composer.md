# EDAN Module Installation with Composer (Advance Guide)

This module contains no dependencies on Composer. Feel free to install this
module using Composer to keep it future-proof.

## Installing the EDAN Module on Drupal 8
### Steps to complete via command line and file directory
1. Download the latest Source Code archive file from https://github.com/Smithsonian/d8-edan-module/releases
2. Unzip the directory into the following directory for the D8 website - <website root>/modules/custom
3. Within the custom folder, confirm the unzipped directory has a folder name of 'edan'
4. Validate the file edan.module exists within the directory <website root>/modules/custom/edan
5. In the website root directory, open composer.json, and under ```merge-plugin.include``` add the following line: ```modules/custom/edan/composer.json``` (below shows how this portion of the JSON file should read)
```
"merge-plugin": {
  "include": [
    "core/composer.json",
    "modules/custom/edan/composer.json"
],
```
6. From the website root directory, run ```composer -V```. If this is unsuccessful, refer to the Installing Composer steps below
7. Run ```composer update drupal/edan``` from the command line in website root directory (see below for additional notes on using composer)


## Post-installation
Refer to [EDAN README.md](https://github.com/Smithsonian/d8-edan-module#post-installation) to complete post-installation steps.

## Installing Composer
### Linux / Unix / OSX
Refer to [installation Steps](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)

### Windows
Refer to [installation Steps](https://getcomposer.org/doc/00-intro.md#installation-windows)
