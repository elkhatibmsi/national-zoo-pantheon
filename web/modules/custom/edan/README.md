# Drupal 8 EDAN Module

This module will bring in EDAN connection, search, and record configurations for use across Drupal 8 websites within SI. 

## Installing the EDAN Module on Drupal 8
### Steps to complete on the back-end
1. Download the latest Source Code archive file from https://github.com/Smithsonian/d8-edan-module/releases
2. Unzip the directory into the following directory for the D8 website - <website root>/modules/custom
3. Within the custom folder, confirm the unzipped directory has a folder name of 'edan'
4. Validate the file edan.module exists within the directory <website root>/modules/custom/edan

### Steps to finish configuring as a website administrator
1. Log into the Drupal website as an admin
2. Navigate to the Extend page, and scroll down to EDAN section.
3. Select the EDAN module and then click 'Install' at the bottom of the page
4. Navigate to the EDAN Connection page (from Configuration -> EDAN API Settings (within the System section)
5. In the URL field - enter http://edan.si.edu
6. Enter credentials for the application ID connecting to EDAN
7. Select EDAN v1.1 on the API Version drop-down
8. Navigate to the Connection Test tab and enter a keyword search to validate the JSON response.


## Post-Installation

The EDAN module provides backend framework for other modules to use. It has no
features for end-users. Either one of the accompanying modules must be enabled,
or a custom module should be created to use framework provided by EDAN module.

### Install/Enable Accompanying Modules

EDAN package contains two addition sub-modules. Please see their README file
for further information and installation instructions. 

#### EDAN Record
Embed records from EDAN in a Drupal node/content or any other entities which
support fields such as Taxonomy and Block.

[README for EDAN Record](https://github.com/Smithsonian/d8-edan-module/tree/master/modules/edan_record)

#### EDAN Search
Allows you to theme and include EDAN MDS-based searches on your site.

[README for EDAN Search](https://github.com/Smithsonian/d8-edan-module/tree/master/modules/edan_search)
