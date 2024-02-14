# result-paper

### prerequisites

- laravel

### setup repo

```bash
$ git clone (https://github.com/Nilusha2024/result-paper.git)
$ Switch to the repo folder
$ cd /path/to/repo
$ Install all the dependencies using composer
```

### composer install
Copy the example env file and make the required configuration changes in the .env file


```bash
$ cp .env.example .env
$ Generate a new application key
$ php artisan jwt:generate
$ Run the database migrations (Set the database connection in .env before migrating)

$ php artisan migrate
$ Start the local development server

php artisan serve
```

