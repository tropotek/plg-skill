#TODO


HOW TO CALCULATE GRADES (GOALS)

  1. For each individual entry we calculate the domain average excluding the zero values.
     So when viewing a single Entries result we have the average caclulated without the zero values.
     (This should be cached for fast data access)
  2. When calculating a students overall grade. 
    1. We compile the domain averages for each of the entries that exclude the zero values. (as above)
       This involves adding all the item values for each domain and dividing the each result by the number of entries
    2. We then add all these domain results for each entry and divide the result by the number of domains. Get the average domain value.
    3. For the final grade result we need to calculate the weighted average of each domain also. This is calculated by multiplying
       each average domain value by the doamin.weight value.  
    4. Finally add all the domain values and divide by the number of domains to get the average grade. 
    5. Divide this value by the scale and multiply by 100 to get the percentage grade
  





  - The entire grade results system of caclculation is not working efficiently, we need to implement
    more caching and more optimised speedy queries to make it fully functional.

  - Grade Results: Refactor the grading system. Would be good to have all grade data in cache tables
    so we can query results over time and generate fast reports of data across all subjects in the same profile. 
    Also optimise the current SQL queries as they are rather large and cumbersome, 
    This makes them hard to edit and maintain.
    
  - Skills Results: Check that the skills results is as optimised as possible. Convert the SQL 
    back to PHP if need be, redesign the tables if needed.   
    
    
    
    
    
    
    