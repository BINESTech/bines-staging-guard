# bines-staging-guard

Must-use plugin. Inert when `wp_get_environment_type()` is `production`.
Everywhere else it:

1. shows a red badge in the admin bar and a strip for logged-in users,
2. catches every `wp_mail()` into Tools → Staging Guard (last 200, nothing sent),
3. refuses outbound HTTP not on the allowlist (`pre_http_request`), logging host + method,
4. sends `X-Robots-Tag: noindex` and pins `blog_public` to 0.

Allowlist ships with wordpress.org hosts, GitHub GET, and **GET only** to
`*.cliniko.com`. Extend per site:

```php
add_filter( 'bines_guard_allowlist', fn( array $list ) => array_merge( $list, array(
	array( 'host' => 'api.example.com', 'methods' => array( 'GET', 'POST' ) ),
) ) );
```

Turn off deliberately on a non-production site with
`define( 'BINES_GUARD_DISABLE', true );` in the environment config.

Install into a Bedrock project: add the VCS repo and
`composer require binestech/bines-staging-guard`.
