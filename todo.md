#TODO


  - We need to research a better way to manage all the entry values and report on them
    Currently it is slow and bulky... ;-?
       
    
    
    
    
    
    
## NOTES 


HOW TO CALCULATE GRADES (GOALS)

  1. For each individual entry we calculate the domain average excluding the zero values.
     So when viewing a single Entries result we have the average calculated without the zero values.
     (This should be cached for fast data access)
  2. When calculating a students overall grade. 
    1. We compile the domain averages for each of the entries that exclude the zero values. (as above)
       This involves adding all the item values for each domain and dividing the each result by the number of entries
    2. We then add all these domain results for each entry and divide the result by the number of domains. Get the average domain value.
    3. For the final grade result we need to calculate the weighted average of each domain also. This is calculated by multiplying
       each average domain value by the doamin.weight value.  
    4. Finally add all the domain values and divide by the number of domains to get the average grade. 
    5. Divide this value by the scale and multiply by 100 to get the percentage grade