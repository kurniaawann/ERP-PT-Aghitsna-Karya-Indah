## Guide running this Project

#### 1. Install PHP 8.3

#### 2. Install Driver MySQL
``` Terminal
sudo dnf install php83-php-mysqlnd
```

### 3. Composer install
``` Terminal
composer install --ignore-platform-reqs
```

### 4. Migrate
```
php artisan migrate
php artisan db:seed
```

### 5. Running
```
php artisan serve
npm run dev // for develop Tailwind CSS
```

### 6. 