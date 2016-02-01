#Steps to deploy this storage layer:
#- create a htpasswd file (somewhere outside the webroot) (htpasswd -c simply-store-htpasswd username);
#- add the .htaccess file in the simply store root:
#- give the www-data user read/write grants for the data directory (chown www-data:www-data data/);
#
# example .htaccess file
<Limit PUT DELETE>
    AuthUserFile /path/outside/webroot/simply-store-htpasswd
    AuthType Basic
    AuthName "Simply store"
    Require valid-user

    RewriteEngine on
    RewriteCond %{REQUEST_METHOD} PUT
    RewriteRule ^(.*)$ store.php [L,END]

    RewriteCond %{REQUEST_METHOD} DELETE
    RewriteRule ^(.*)$ store.php [L,END]
</Limit>
<Limit GET POST>
	RewriteEngine on
	RewriteRule ^logout$ logout.php [L,END]
</Limit>
Options +Indexes
