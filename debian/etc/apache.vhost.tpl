<VirtualHost *:443>
      SSLEngine on
      SSLProtocol all -SSLv2
      SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
      SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
      Header always set Strict-Transport-Security "max-age=31536000"

      ServerName __SERVER_NAME__
      #ServerAlias www.example.com

      ServerAdmin __SERVER_ADMIN__

      DocumentRoot /usr/share/airtime/public
      DirectoryIndex index.php

      <Directory /usr/share/airtime/public>
              Options -Indexes FollowSymLinks MultiViews
              AllowOverride all
              Order allow,deny
              Allow from all
      </Directory>
</VirtualHost>

<VirtualHost *:80>
      ServerName __SERVER_NAME__

      ServerAdmin __SERVER_ADMIN__

      DocumentRoot /usr/share/airtime/public
      Redirect permanent /login https://__SERVER_NAME__/login

      SetEnv APPLICATION_ENV "production"

      <Directory /usr/share/airtime/public>
              Options -Indexes FollowSymLinks MultiViews
              AllowOverride All
              Order deny,allow
              Deny from all
              Allow from localhost
      </Directory>
</VirtualHost> 
