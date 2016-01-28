Hier komt de code voor een eenvoudige store laag met image support

Deze htaccess file in je simply-store checkout neerzetten
# example .htaccess file
<Limit PUT DELETE>
    AuthUserFile /path/outside/webroot/.htpasswd
    AuthType Basic
    AuthName "Simply store"
    Require valid-user

    RewriteEngine on
    RewriteCond %{REQUEST_METHOD} PUT
    RewriteRule ^(.*)$ put.php [L,END]
</Limit>
Options +Indexes
