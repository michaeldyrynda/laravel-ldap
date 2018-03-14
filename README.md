# Adldap2 Laravel middleware

The excellent [Adldap2-Laravel](https://github.com/Adldap2/Adldap2-Laravel/) package makes it super simple to integrate your Laravel application with an LDAP server to authenticate your users, but as these users can be managed externally to your application, it's not always possible to manage their access if they are already logged in.

This package not only allows you to define groups your users must belong to in order to authenticate, but will also ensure that those groups continue to exist throughout a user's access, not just at the time of authentication.

## Installation

    composer require dyrynda/laravel-ldap

Once the package is installed, publish the configuration file

    php artisan vendor:publish --tag="laravel-ldap"

Run the package migrations

    php artisan migrate

## Configuration

The two keys you are likely to change are the `allowed_groups` and `username` keys.

* `allowed_groups` contains the distinguished names for allowed groups that users must have in order to be able to authenticate.
* `username` is the database field that your application users can be found by in the `users` table, and will match the username used to authenticate with your application.

## Usage

In order to restrict authentication of users to your application using the `allowed_groups` key, add the `GroupAccessScope` to the `scopes` key of the `adldap_auth` configuration file. A user will only be able to authenticate if they are a member of each group defined in the `allowed_groups` array.

This scope will ensure that users can only login if they are members of the given groups, but does nothing to protect your application from users that are already logged in from accessing it should their access be revoked in the directory server.

To combat this, you can add the following to to the `$routeMiddleware` property of your `app/Http/Kernel.php`.

    'ldap' => \Dyrynda\Ldap\Http\Middleware\LdapMiddleware::class

This middleware can then be applied to your routes to ensure the following:

* Your LDAP user exists in the directory and continues to be a member of the `allowed_groups`
* Authenticated users have not recently changed their password
* Authenticated users' passwords are not due to expire within 5 days

Should any of these conditions evaluate to false, the user will be logged out and directed to login to your application again.

## Support
If you are having general issues with this repository, feel free to contact me on [Twitter](https://twitter.com/michaeldyrynda).

If you believe you have found an issue, please report it using the [GitHub issue tracker](https://github.com/michaeldyrynda/laravel-ldap/issues), or better yet, fork the repository and submit a pull request.

If you're using this repository, I'd love to hear your thoughts. Thanks!
