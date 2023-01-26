# EDAN Search Module

Allows you to theme and include EDAN MDS-based searches on your site.

It is a sub-module of EDAN module.

## Installation

1. Make sure the parent EDAN module has been installed/enabled and configured.
[README for EDAN](https://github.com/Smithsonian/d8-edan-module)
2. Navigate to the Extend page (/admin/modules), and scroll down to EDAN section.
3. Select the EDAN Search module and then click 'Install' at the bottom of
   the page.
4. Clear all caches by going to Manage -> Configuration ->
Development/Performances (/admin/config/development/performance).
5. Verify that the search page loads at D8-Website-URL/search/edan.

## Post-Installation

1. This module requires configuring search parameters.
2. Go to the configuration page for EDAN Search module at Manage ->
Configuration -> EDAN -> Search Settings (/admin/config/system/edan/search).

Note: If you don't see the Search settings tab in Step 2, please clear all
caches.

## Facet Json
Route to get the facets returned is /facet/{edan_search}.  Pass the following url parameters:
1. facet_field: limit the results to the facet_field provided
2. facet_prefix:  limits the terms on which to facet to those starting with the given string prefix
3. facet_sort:  determines the ordering of the facet field constraints. index sorts alphabetically;
4. count sorts constraints by count
5. page: by default the call will return the facet starting from the beginning.  if you want to show
facets from what the search has returned, then set page=1
6. show_all: set to true or 1 to show all facets up to 1k
