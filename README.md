# 
1. To use the "Files" class, the owner of the folder 'files' must be apache:
  cd /var/www/<project_name>; chown -R apache:apache files/
2. To use the "Images" class, the owner of the folders 'images' / 'images_org' must be apache:
 cd /var/www/<project_name>; chown -R apache:apache files/
 Also, add the following rules to ngnix:
  location ^~ /images{
      index site/www/index.php;

      root /var/www/unisped/;

      try_files $uri $uri/ /index.php?$args;
  }

