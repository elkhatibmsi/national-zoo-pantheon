# EDAN Record Module

Embed records from EDAN in a Drupal node/content or any other entities which
support fields such as Taxonomy and Block.

It is a sub-module of EDAN module.

## Installation

1. Make sure the parent EDAN module has been installed/enabled and configured.
[README for EDAN](https://github.com/Smithsonian/d8-edan-module)
2. Navigate to the Extend page (/admin/modules), and scroll down to EDAN section.
3. Select the EDAN Record module and then click 'Install' at the bottom of
   the page.
4. Clear all caches by going to Manage -> Configuration -> 
Development/Performances (/admin/config/development/performance).
5. This module requires no further configuration at the moment. Follow post-
   installation instructions for using this module.
   

## Post-Installation

Make sure you are still logged in as an admin.

### How to embed EDAN record in a node/content page

#### Adding field to content type

1. Navigate to the Structure page and then to the Content types page.
2. You may add a new content type, or choose one of the existing types.
3. Under the Operations column next to your desired content type, select
manage fields.
4. Click on Add Field button at the top.
5. Under Add a new field drop down, select EDAN Record option, enter a label
for this field and click on Save and continue.
6. Select number of values (i.e. number of records) this field should allow
to be embedded on a node/content page, and click on Save field settings.
7. You may enter Helper text, default value and make this field required
should you have to.
8. Click on Save settings.
9. Switch to Manage display tab (not Manage form display).
10. On this page, you can select the order of display and configure additional
settings for EDAN Record field.
    1. To change order, drag the field up or down and click on Save.
    2. To change additional settings for EDAN Record field, click on the gear
    icon next to it.
        1. You can configure options such as thumbnail size and link.
        2. Make sure to click on Update after changing additional settings, and 
      click on Save.

#### Creating content with an embedded EDAN record

1. Navigate to the Content administration page.
2. Click on Add content button.
    1. Note: If there are multiple content types, you will have to select the
    content type with the EDAN Record field.
3. Enter title and values for other fields as needed.
4. For the EDAN Record field, you will need to enter the URL value of an EDAN
record.     
    1. What is the URL value of an EDAN record?
        1. URL value has format edan_object_type:unique_identifier
        2. For example, for an EDAN Record titled 'Eli Thayer' from NPGCAP
        unit, has the url value of edanmdm:npg_BP105.
5. Save content

Note: Drupal usually caches rendered pages. 
 
### How to embed EDAN record in a custom block

Note: Make sure that the Custom Block module is enabled.

#### Adding field to block type
 
1. Navigate to the Structure page and then to the Block layout page.
2. Switch to Custom block library tab, and then go to Block types sub-tab.
3. You may add a new block type, or choose one of the existing types.
4. Follow steps 3 to 10 from Adding field to content type guide.

#### Creating block with an embedded EDAN record

1. Navigate to the Structure page and then to the Block layout page.
2. Switch to Custom block library tab, and remain on Blocks sub-tab.
3. Click on Add custom block button.
    1. Note: If there are multiple block types, you will have to select the
    block type with the EDAN Record field.
4. Enter block description and values for other fields as needed.
5. Follow step 5 of Creating content with an embedded EDAN record guide.
6. Save the block
7. Switch back to the Block layout tab        
8. Place your block in the desired region/section.

Note: Drupal usually caches rendered blocks. 

### How to embed OGMT record in a node/content page

#### Adding field to content type

1. Navigate to the Structure page and then to the Content types page.
2. You may add a new content type, or choose one of the existing types.
3. Under the Operations column next to your desired content type, select
manage fields.
4. Click on Add Field button at the top.
5. Under Add a new field drop down, select OGMT Record option, enter a label
for this field and click on Save and continue.
6. Select number of values (i.e. number of records) to be embedded on a
node/content page, and click on Save field settings.
7. You may enter Helper text, default value and make this field required
should you have to.
8. Click on Save settings.
9. Switch to Manage Display tab (NOT the Manage Form display).
10. On this page, you can select the order of display and configure additional
settings for OGMT Record field.
    1. To change order, drag the field up or down and click on Save.
    2. To change additional settings for OGMT Record field, click on the gear
    icon next to it.
        1. You can configure options such as feature image and thumbnail size.
        2. Make sure to click on Update after changing additional settings, and 
      click on Save.

#### Creating content with an embedded OGMT Record

1. Navigate to the Content administration page.
2. Click on Add content button.
    1. Note: If there are multiple content types, you will have to select the
    content type with the EDAN Record field.
3. Enter title and values for other fields as needed.
4. For the OGMT Record field, you will need to select the type of value being
entered (e.g. URL or ID), enter value of Object Group (URL or ID), and 
enter value for Object Group Page (optional). 
    1. In Step 4, you may NOT mix-and-match values. If you are going to enter
    both Object Group and Object Group Page values, they must be consistent. 
    2. Examples:
        1. Object Group URL: adding-machines
        2. Object Group ID: jdi-1477398492591-1477398492592-0
        3. Object Group Page URL: stylus-operated-adding-machines
        4. Object Group Page ID: jdi-1477398506451-1477398506452-0
5. Save content

Note: Drupal usually caches rendered pages. 
 

