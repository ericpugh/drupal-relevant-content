Relevant Content
============
Overview
------------
A Drupal 8 module that creates a block of other relevant content based on the number of like taxonomy terms the content
references.

Dependencies
-----------
* Taxonomy
* Block

Installation
-----------
1. Place the module files in your modules directory.
2. Enable the Relevant Content module at (/admin/modules).
3. Place a Relevant Content block on the "Block Layout" admin page (/admin/structure/block).
4. Set the block settings:
    * Relevant Content serach criteria, the Vocabularies to use to calculate relevant content.
    * Number of items, the maximum number of relevant nodes to output.
    * Allowed results types, the content types to output.
    * View mode, the view mode of the output. (i.e. Teaser)
5. Set the remaining block settings as usually, including region and block visibility. Note: The Relevant Content block 
should be restricted to a Content Type or full node page, Relevant Content blocks output on other pages with return no
results.


