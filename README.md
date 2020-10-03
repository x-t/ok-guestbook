# ok-guestbook
A simple, small but inefficient, guestbook written in pure PHP (while on PCP).  

## how to set it up
files  
`index.php` - the index page (from where you post/see other posts)  
`result.php` - posts the post  
`comments.json` - the comments file, stored in pure json  
are expected to be in your webroot  

files  
`bans.json` - banned ip list  
`log.json` - one log file on who did what  
are expected to not be in your webroot, it defaults to `/home/www-data` by default  

change the directories in which the files above should be stored at, set the appropriate permissions for the folders where PHP is going to write files to and you're set
