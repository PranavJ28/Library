## To create an `admin` account

- Open terminal and run `php artisan tinker`
- Add a new user as --
```php
\App\Models\User::create([
    'name' => 'John Doe', // or any other name and email
    'email' => 'johndoe@gmail.com',
    'role' => 'admin',
    'password' => Illuminate\Support\Facades\Hash::make('12345678')
]);
```