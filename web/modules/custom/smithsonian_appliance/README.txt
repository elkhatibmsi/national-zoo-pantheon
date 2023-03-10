$Id$

-- Outdated for D8 --

-- SUMMARY --

The Smithsonian Appliance module provides an interface to obtain search results from a dedicated Open Source Search (OSS) hardware device. The module can act as a replacemet for core Search, or it may operate in tandem with it.

If you will use core search along with this module, core search results will show up normally at search/{search terms}, while Smithsonian Appliance searches will show up at gsearch/{search terms} by default. You can also configure Smithsonian Appliance searches to appear at a path other than gsearch, even taking over the Core Search module's path.

Since search index management for the OSS searches is offloaded to the OSS device, utilizing the module is a two-tier solution:

  (1) search management with the OSS and its assocated (Drupal-external) tasks, and
  (2) defining the commiunication interface for Drupal.

This module simply defines the communication interface for Drupal.


-- REQUIREMENTS --

Before using this module, you (obviously) must have a dedicated Open Source Search (OSS) device, and you must also set up a collection and frontend on the GSA that can be accessed publicly. To produce results that include Drupal content, the GSA crawl must have visited your drupal installation and indexed the desired content. This will allow off-platform content to be integrated into your drupal search solution.

Note that the PHP cURL library is required.

-- INSTALLATION --

### Using Composer
Since this module isn't published as a package to a repository, using composer to install the module necessitates modifying the root composer.json file for the site.

Option 1 is to add the module as a package by adding an entry to `repositories:[]` (see below) and running `composer require drupal/smithsonian_appliance`:
```
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
        {
            "type": "package",
            "package": {
                "name": "drupal/smithsonian_appliance",
                "version": "1.0.0",
                "type": "drupal-custom-module",
                "source": {
                    "url": "https://github.com/Smithsonian/si_ossa.git",
                    "type": "git",
                    "reference": "main"
                }
            }
        }
    ],
```

Option 2 is to install by adding the location of the module as an additional repository and downloading the module to that location, e.g. 2nd entry here:

```
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
        {
            "type": "path",
            "url": "/path/to/modulefiles/smithsonian_appliance"
        }
    ],
```


-- CONFIGURATION --

The module needs to be configured to connect to your GSA device, which can be found here...

  * admin/config/search/smithsonian_appliance/settings

Or in the "Search & Metadata" fieldset on the configuration page, click on "Search Smithsonian Appliance".

-- BLOCKS --

The module provides two blocks:

  (1) Search Form
  (2) Related Searches

Both blocks will need to be assigned to a region, but the Related Searches block is preconfigured to only appear on results pages. Neither block is required to be used, as you can access the search form on the search page located here by default:

  * gsearch/

To setup your blocks, administer them in the normal way at

  * admin/structure/block

-- ONEBOX INTEGRATION --

This module provides a basic framework for adding onebox modules to the search interface. For the purposes of this documentation, we assume you already have oneboxes configured and running on your search appliance.

Onebox modules are represented as blocks--one block per onebox. To add onebox blocks to Drupal, add each name (exactly as it appears in the search appliance) to the "onebox modules" text area on the module configuration page, each onebox on its own line.

After saving your configuration changes, blocks will be created for each onebox. You'll want to place these blocks on the search result page. You can place them as described above (in the blocks section), or via your preferred Drupal layout module (like Context or Panels).

-- TESTING --

Automated tests have been written that fall into two categories:

  (1) Basic testing that doesn't require a connection to your GSA
  (2) Connectivity and results listings tests

To execute the second class of tests, you will need to provide an input file for the SimpleTest browser to configure your module. See the example file:

  * smithsonian_appliance/testing/test-settings.example.inc
