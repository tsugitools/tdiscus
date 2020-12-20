Make the names of these unique because they 
turn into id="" values on the <template> tags
and when a browser is polyfilled, it folds the
templates into the base document.  So they need
to be unique across the whole document.

