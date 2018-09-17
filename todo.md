#TODO


  - The Items need to be sorted on a per subject basis. Currently they are 
    sorted per collection this is weill influence the student results for all subject??
    
  - __Collection__: remove the `profile_id` and replace it with an `institution_id` field.
    - __Item__: We need to remove the `category_id`, `domain_id`
       The category and item link should be made with the `subject_id`
      (`domain_id` can be moved to the category, treat it as a parent category)
    - __collection_subject__: This tables rol will now change so that we can enable/disable collections 
      for a subject. when a collection is enabled for a subject and if no items are setup then the item links
      should be copied from the previous most current subject. Also need to implement a nice edit items
      page that allows for the addition/edit/link of items.
    - __UID__: this update should make the need of a UID in all tables deprecated.

  - __De-couple__: Try to remove any ref to profile_id as this breaks the plugin for other sites.
    Not a high priority but something to keep in mind.
